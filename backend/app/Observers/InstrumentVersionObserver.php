<?php

namespace App\Observers;

use App\Enums\InstrumentStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\InstrumentVersion;

class InstrumentVersionObserver
{
    public function updating(InstrumentVersion $version): void
    {
        $originalStatus = InstrumentStatus::from($version->getRawOriginal('status'));

        if (! $originalStatus->editable() && $version->isDirty([
            'survey_template_id', 'major', 'minor', 'patch', 'comparability_status', 'change_reason', 'created_by',
        ])) {
            throw new DomainRuleViolation('instrument_version_locked', 'Metadata versi yang sedang direview atau sudah disetujui tidak dapat diubah.');
        }
    }

    public function deleting(InstrumentVersion $version): void
    {
        if (! $version->status->editable() || $version->surveys()->exists()) {
            throw new DomainRuleViolation('instrument_version_in_use', 'Versi hanya dapat dihapus saat draft/returned dan belum digunakan survey.');
        }
    }
}
