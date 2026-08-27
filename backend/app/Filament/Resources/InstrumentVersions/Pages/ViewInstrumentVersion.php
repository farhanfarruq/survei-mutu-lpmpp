<?php

namespace App\Filament\Resources\InstrumentVersions\Pages;

use App\Enums\InstrumentStatus;
use App\Exceptions\DomainRuleViolation;
use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Models\InstrumentVersion;
use App\Services\InstrumentLifecycle;
use App\Services\InstrumentVersioning;
use Filament\Actions\Action;
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
            Action::make('editSimple')
                ->label('Lanjutkan edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => CreateSurveyForm::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => auth()->user()->can('update', $this->record)),
            Action::make('submitReview')->label('Ajukan untuk diperiksa')->visible(fn () => auth()->user()->can('submitReview', $this->record))->requiresConfirmation()->action(fn (InstrumentLifecycle $service) => $this->run(fn () => $service->submitForReview($this->record, auth()->user()))),
            Action::make('return')->label('Kembalikan')->color('warning')->visible(fn () => $this->record->status === InstrumentStatus::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Alasan')->required()])->action(fn (array $data, InstrumentLifecycle $service) => $this->run(fn () => $service->returnToDraft($this->record, auth()->user(), $data['note']))),
            Action::make('approve')->label('Setujui')->color('success')->visible(fn () => $this->record->status === InstrumentStatus::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Catatan')->nullable()])->action(fn (array $data, InstrumentLifecycle $service) => $this->run(fn () => $service->approve($this->record, auth()->user(), $data['note'] ?? null))),
            Action::make('revision')
                ->label('Buat versi revisi')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn (): bool => $this->getRecord()->getRawOriginal('status') === InstrumentStatus::Approved->value && auth()->user()->can('duplicate', $this->getRecord()))
                ->form([
                    Select::make('change_type')->label('Jenis perubahan')->options([
                        'small' => 'Perbaikan kecil, makna tetap sama',
                        'content' => 'Perubahan isi atau pertanyaan',
                        'major' => 'Perubahan besar atau metode',
                    ])->default('content')->required(),
                    Textarea::make('reason')->label('Alasan perubahan')->required(),
                ])
                ->action(function (array $data, InstrumentVersioning $service): void {
                    [$bump, $comparability] = match ($data['change_type']) {
                        'small' => ['patch', 'comparable'],
                        'major' => ['major', 'not_comparable'],
                        default => ['minor', 'partial'],
                    };
                    $copy = $this->run(fn () => $service->duplicate($this->getRecord(), auth()->user(), $bump, $data['reason'], $comparability));
                    if ($copy) {
                        $this->redirect(CreateSurveyForm::getUrl(['record' => $copy]));
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
