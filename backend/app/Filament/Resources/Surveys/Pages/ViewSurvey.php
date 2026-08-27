<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use App\Models\User;
use App\Services\SurveyDuplication;
use App\Services\SurveyLifecycle;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewSurvey extends ViewRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => $this->record->state->configurationEditable()),
            Action::make('preflight')->label('Periksa kesiapan')->action(function (SurveyLifecycle $service): void {
                $errors = $service->preflight($this->record);
                Notification::make()->title($errors === [] ? 'Preflight lulus' : 'Preflight belum lulus')->body($errors === [] ? 'Tidak ada blocker publikasi.' : implode(' ', $errors))->color($errors === [] ? 'success' : 'warning')->send();
            }),
            Action::make('submitReview')->label('Kirim untuk review')->visible(fn () => auth()->user()->can('submitReview', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->submitForReview($this->record, auth()->user()))),
            Action::make('return')->label('Kembalikan')->color('warning')->visible(fn () => $this->record->state === SurveyState::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Alasan')->required()])->action(fn (array $data, SurveyLifecycle $service) => $this->run(fn () => $service->returnToDraft($this->record, auth()->user(), $data['note']))),
            Action::make('approve')->label('Setujui')->color('success')->visible(fn () => $this->record->state === SurveyState::InReview && auth()->user()->can('review', $this->record))->form([Textarea::make('note')->label('Catatan')])->action(fn (array $data, SurveyLifecycle $service) => $this->run(fn () => $service->approve($this->record, auth()->user(), $data['note'] ?? null))),
            Action::make('publish')->label('Publikasikan')->color('success')->visible(fn () => auth()->user()->can('publish', $this->record))->requiresConfirmation()->modalHeading('Pastikan sasaran dan jadwal sudah benar')->modalDescription(fn (): string => $this->publicationSummary())->modalSubmitActionLabel('Ya, publikasikan')->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->publish($this->record, auth()->user()))),
            Action::make('reschedule')
                ->label('Ubah jadwal aman')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn (): bool => auth()->user()->can('reschedule', $this->survey()))
                ->form([
                    TextInput::make('name')->label('Nama survei')->default(fn (): string => $this->survey()->name)->required()->maxLength(240),
                    DateTimePicker::make('opens_at')->label('Mulai pengisian')->default(fn () => $this->survey()->opens_at)->visible(fn (): bool => $this->survey()->getRawOriginal('state') === SurveyState::Scheduled->value)->required(fn (): bool => $this->survey()->getRawOriginal('state') === SurveyState::Scheduled->value)->seconds(false)->timezone('Asia/Jakarta'),
                    DateTimePicker::make('closes_at')->label('Batas akhir')->default(fn () => $this->survey()->closes_at)->required()->seconds(false)->timezone('Asia/Jakarta'),
                    Select::make('action_owner_id')->label('Penanggung jawab')->options(fn () => User::role(['super_admin', 'admin_lpmpp', 'leader'])->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->default(fn () => $this->survey()->action_owner_id)->required()->searchable(),
                ])
                ->requiresConfirmation()
                ->modalDescription('Isi formulir, sasaran, privasi, dan jawaban lama tidak akan berubah.')
                ->action(fn (array $data, SurveyLifecycle $service) => $this->run(fn () => $service->reschedule($this->survey(), auth()->user(), $data))),
            Action::make('close')->label('Tutup')->color('warning')->visible(fn () => auth()->user()->can('close', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->close($this->record, auth()->user()))),
            Action::make('archive')->label('Arsipkan')->visible(fn () => auth()->user()->can('archive', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->archive($this->record, auth()->user()))),
            Action::make('duplicate')->label('Buat salinan untuk diperbaiki')->visible(fn () => auth()->user()->can('duplicate', $this->record))->form([
                TextInput::make('name')->label('Nama survei baru')->default(fn (): string => 'Salinan '.$this->record->name)->required()->maxLength(240),
            ])->action(function (array $data, SurveyDuplication $service): void {
                $code = 'SURVEI-'.Str::upper(Str::uuid()->toString());
                $new = $this->run(fn () => $service->duplicate($this->record, auth()->user(), $code, $data['name']));
                if ($new) {
                    $this->redirect(SurveyResource::getUrl('edit', ['record' => $new]));
                }
            }),
            Action::make('revision')
                ->label('Buat revisi survei')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn (): bool => in_array($this->survey()->getRawOriginal('state'), [SurveyState::Scheduled->value, SurveyState::Active->value, SurveyState::Closed->value, SurveyState::Archived->value], true) && auth()->user()->can('duplicate', $this->survey()))
                ->form([
                    Select::make('change_type')->label('Jenis perubahan')->options([
                        'small' => 'Perbaikan kecil, makna tetap sama',
                        'content' => 'Perubahan isi atau pertanyaan',
                        'major' => 'Perubahan besar atau metode',
                    ])->default('content')->required(),
                    Textarea::make('reason')->label('Alasan perubahan')->required(),
                    TextInput::make('name')->label('Nama survei baru')->default(fn (): string => 'Revisi '.$this->survey()->name)->required()->maxLength(240),
                    DateTimePicker::make('opens_at')->label('Mulai pengisian baru')->required()->seconds(false)->timezone('Asia/Jakarta'),
                    DateTimePicker::make('closes_at')->label('Batas akhir baru')->required()->seconds(false)->timezone('Asia/Jakarta'),
                ])
                ->action(function (array $data, SurveyDuplication $service): void {
                    $new = $this->run(fn () => $service->revise($this->survey(), auth()->user(), $data));
                    if ($new instanceof Survey) {
                        $this->redirect(CreateSurveyForm::getUrl(['record' => $new->instrument_version_id]));
                    }
                }),
        ];
    }

    private function publicationSummary(): string
    {
        $this->record->loadMissing(['targets.targetUnit', 'targets.respondentGroup']);
        $targets = $this->record->targets
            ->map(fn ($target) => $target->target_type === 'organizational_unit'
                ? $target->targetUnit?->name
                : $target->respondentGroup?->name)
            ->filter()
            ->unique()
            ->implode(', ');
        $start = $this->record->opens_at->timezone('Asia/Jakarta')->format('d/m/Y H:i');
        $end = $this->record->closes_at->timezone('Asia/Jakarta')->format('d/m/Y H:i');

        return "Survei akan tersedia untuk {$targets} mulai {$start} WIB sampai {$end} WIB.";
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

    private function survey(): Survey
    {
        /** @var Survey $survey */
        $survey = $this->record;

        return $survey;
    }
}
