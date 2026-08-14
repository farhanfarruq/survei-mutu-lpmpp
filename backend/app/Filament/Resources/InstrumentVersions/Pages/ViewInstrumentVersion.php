<?php

namespace App\Filament\Resources\InstrumentVersions\Pages;

use App\Enums\InstrumentStatus;
use App\Exceptions\DomainRuleViolation;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Models\InstrumentVersion;
use App\Services\InstrumentLifecycle;
use App\Services\InstrumentVersioning;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInstrumentVersion extends ViewRecord
{
    protected static string $resource = InstrumentVersionResource::class;

    public function getRecord(): InstrumentVersion
    {
        /** @var InstrumentVersion $record */
        $record = parent::getRecord();

        return $record->loadMissing('sections.questions.scale');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => $this->record->isEditable()),
            Action::make('submitReview')->label('Kirim untuk review')->visible(fn () => auth()->user()->can('submitReview', $this->record))->requiresConfirmation()->action(fn (InstrumentLifecycle $service) => $this->run(fn () => $service->submitForReview($this->record, auth()->user()))),
            Action::make('return')->label('Kembalikan')->color('warning')->visible(fn () => $this->record->status === InstrumentStatus::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Alasan')->required()])->action(fn (array $data, InstrumentLifecycle $service) => $this->run(fn () => $service->returnToDraft($this->record, auth()->user(), $data['note']))),
            Action::make('approve')->label('Setujui')->color('success')->visible(fn () => $this->record->status === InstrumentStatus::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Catatan')->nullable()])->action(fn (array $data, InstrumentLifecycle $service) => $this->run(fn () => $service->approve($this->record, auth()->user(), $data['note'] ?? null))),
            Action::make('duplicate')->label('Buat versi baru')->visible(fn () => auth()->user()->can('duplicate', $this->record))->form([
                Select::make('bump')->options(['major' => 'Major', 'minor' => 'Minor', 'patch' => 'Patch'])->required(),
                Select::make('comparability')->options(['comparable' => 'Comparable', 'partial' => 'Sebagian', 'not_comparable' => 'Tidak comparable'])->required(),
                Textarea::make('reason')->label('Alasan perubahan')->required(),
            ])->action(function (array $data, InstrumentVersioning $service): void {
                $new = $this->run(fn () => $service->duplicate($this->record, auth()->user(), $data['bump'], $data['reason'], $data['comparability']));
                if ($new) {
                    $this->redirect(InstrumentVersionResource::getUrl('edit', ['record' => $new]));
                }
            }),
        ];
    }

    private function run(callable $operation): mixed
    {
        try {
            $result = $operation();
            $this->record->refresh();
            Notification::make()->title('Operasi berhasil')->success()->send();

            return $result;
        } catch (DomainRuleViolation $exception) {
            Notification::make()->title('Operasi ditolak')->body($exception->getMessage())->danger()->send();

            return null;
        }
    }
}
