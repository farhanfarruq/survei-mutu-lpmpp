<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyState;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use App\Services\OrganizationalScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SurveyWorkflowQueue extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $table
            ->query(Survey::query()
                ->with(['ownerUnit', 'period'])
                ->whereIn('owner_unit_id', $unitIds)
                ->whereIn('state', ['returned', 'in_review', 'approved', 'draft', 'scheduled', 'active'])
                ->orderByRaw("case state when 'returned' then 1 when 'in_review' then 2 when 'approved' then 3 when 'draft' then 4 when 'scheduled' then 5 else 6 end")
                ->latest('updated_at')
                ->limit(6))
            ->heading('Antrean kerja survei')
            ->description('Survei yang masih membutuhkan tindakan atau pemantauan.')
            ->columns([
                TextColumn::make('name')
                    ->label('Survei')
                    ->description(fn (Survey $record): string => $record->ownerUnit?->name ?? 'Unit belum ditentukan')
                    ->searchable()
                    ->wrap()
                    ->width('42%')
                    ->url(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record])),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->width('11rem')
                    ->formatStateUsing(fn (SurveyState|string $state): string => self::statusLabel($state))
                    ->color(fn (Survey $record): string => match ($record->getRawOriginal('state')) {
                        'returned' => 'danger',
                        'in_review', 'approved' => 'warning',
                        'active' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('next_action')
                    ->label('Tindakan')
                    ->state(fn (Survey $record): string => self::nextAction($record->state))
                    ->description(fn (Survey $record): string => $record->period?->name ?? 'Periode belum ditentukan')
                    ->weight('medium')
                    ->wrap()
                    ->width('16rem')
                    ->url(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record])),
                TextColumn::make('responses_count')->label('Jawaban')->numeric()->width('5rem'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y')
                    ->description(fn (Survey $record): string => $record->updated_at?->timezone('Asia/Jakarta')->format('H:i') ?? '—')
                    ->width('7rem'),
            ])
            ->recordUrl(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record]))
            ->paginated(false)
            ->emptyStateHeading('Tidak ada survei dalam antrean')
            ->emptyStateDescription('Gunakan tombol Buat formulir survei untuk memulai pekerjaan baru.');
    }

    private static function statusLabel(SurveyState|string $state): string
    {
        $state = $state instanceof SurveyState ? $state->value : $state;

        return match ($state) {
            'draft' => 'Draf',
            'in_review' => 'Menunggu pemeriksaan',
            'returned' => 'Perlu diperbaiki',
            'approved' => 'Disetujui',
            'scheduled' => 'Terjadwal',
            'active' => 'Sedang berjalan',
            'closed' => 'Selesai',
            'archived' => 'Diarsipkan',
            default => $state,
        };
    }

    private static function nextAction(SurveyState|string $state): string
    {
        $state = $state instanceof SurveyState ? $state->value : $state;

        return match ($state) {
            'returned' => 'Lengkapi dan ajukan ulang',
            'draft' => 'Lengkapi lalu ajukan',
            'in_review' => 'Periksa dan putuskan',
            'approved' => 'Jadwalkan publikasi',
            'scheduled' => 'Pantau jadwal',
            'active' => 'Pantau partisipasi',
            'closed', 'archived' => 'Lihat rincian',
            default => 'Lihat rincian',
        };
    }
}
