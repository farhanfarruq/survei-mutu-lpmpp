<?php

namespace App\Filament\Resources\Surveys\Pages;

use App\Enums\SurveyState;
use App\Exceptions\DomainRuleViolation;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Services\SurveyDuplication;
use App\Services\SurveyLifecycle;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
            Action::make('publish')->label('Publikasikan')->color('success')->visible(fn () => auth()->user()->can('publish', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->publish($this->record, auth()->user()))),
            Action::make('close')->label('Tutup')->color('warning')->visible(fn () => auth()->user()->can('close', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->close($this->record, auth()->user()))),
            Action::make('archive')->label('Arsipkan')->visible(fn () => auth()->user()->can('archive', $this->record))->requiresConfirmation()->action(fn (SurveyLifecycle $service) => $this->run(fn () => $service->archive($this->record, auth()->user()))),
            Action::make('duplicate')->label('Duplikasi')->visible(fn () => auth()->user()->can('duplicate', $this->record))->form([
                TextInput::make('code')->required()->maxLength(80)->unique('surveys', 'code'), TextInput::make('name')->label('Nama')->required()->maxLength(240),
            ])->action(function (array $data, SurveyDuplication $service): void {
                $new = $this->run(fn () => $service->duplicate($this->record, auth()->user(), $data['code'], $data['name']));
                if ($new) {
                    $this->redirect(SurveyResource::getUrl('edit', ['record' => $new]));
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
