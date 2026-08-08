<?php

namespace App\Services;

use App\Enums\InstrumentStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\InstrumentVersion;
use App\Models\User;

class InstrumentLifecycle
{
    public function __construct(private readonly InstrumentPreflight $preflight) {}

    public function submitForReview(InstrumentVersion $version, User $actor): InstrumentVersion
    {
        if (! $version->status->editable()) {
            throw new DomainRuleViolation('invalid_instrument_transition', 'Hanya versi draft/returned yang dapat dikirim untuk review.');
        }
        $this->assertPreflight($version);

        $version->forceFill([
            'status' => InstrumentStatus::InReview,
            'content_hash' => $this->preflight->contentHash($version),
            'submitted_by' => $actor->id,
            'submitted_at' => now(),
            'review_note' => null,
        ])->save();

        $this->audit($version, $actor, 'submitted_for_review');

        return $version->refresh();
    }

    public function returnToDraft(InstrumentVersion $version, User $actor, string $note): InstrumentVersion
    {
        if ($version->status !== InstrumentStatus::InReview) {
            throw new DomainRuleViolation('invalid_instrument_transition', 'Hanya versi in-review yang dapat dikembalikan.');
        }
        if (trim($note) === '') {
            throw new DomainRuleViolation('review_note_required', 'Alasan pengembalian wajib diisi.');
        }

        $version->forceFill(['status' => InstrumentStatus::Returned, 'review_note' => trim($note), 'content_hash' => null])->save();
        $this->audit($version, $actor, 'returned');

        return $version->refresh();
    }

    public function approve(InstrumentVersion $version, User $actor, ?string $note = null): InstrumentVersion
    {
        if ($version->status !== InstrumentStatus::InReview) {
            throw new DomainRuleViolation('invalid_instrument_transition', 'Hanya versi in-review yang dapat disetujui.');
        }
        if ($version->created_by === $actor->id) {
            throw new DomainRuleViolation('self_approval_forbidden', 'Pembuat versi tidak boleh menjadi approver tunggal.');
        }
        $this->assertPreflight($version);

        $currentHash = $this->preflight->contentHash($version);
        if (! hash_equals((string) $version->content_hash, $currentHash)) {
            throw new DomainRuleViolation('instrument_hash_changed', 'Konten berubah setelah dikirim untuk review.');
        }

        $version->forceFill([
            'status' => InstrumentStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'review_note' => $note ? trim($note) : null,
        ])->save();
        $this->audit($version, $actor, 'approved');

        return $version->refresh();
    }

    /** @return list<string> */
    public function preflight(InstrumentVersion $version): array
    {
        return $this->preflight->errors($version);
    }

    private function assertPreflight(InstrumentVersion $version): void
    {
        $errors = $this->preflight->errors($version);
        if ($errors !== []) {
            throw new DomainRuleViolation('instrument_preflight_failed', implode(' ', $errors));
        }
    }

    private function audit(InstrumentVersion $version, User $actor, string $event): void
    {
        activity('instrument')->performedOn($version)->causedBy($actor)->event($event)->withProperties(['status' => $version->status->value, 'content_hash' => $version->content_hash])->log("Instrument {$event}");
    }
}
