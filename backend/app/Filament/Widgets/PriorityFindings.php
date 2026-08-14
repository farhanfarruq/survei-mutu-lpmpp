<?php

namespace App\Filament\Widgets;

use App\Models\Finding;
use App\Services\OrganizationalScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class PriorityFindings extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $unitIds = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $table
            ->query(Finding::query()->with('ownerUnit')->whereIn('owner_unit_id', $unitIds)->whereNotIn('state', ['verified', 'rejected'])->orderBy('due_on'))
            ->heading('Temuan yang perlu perhatian')
            ->description('Prioritas berdasarkan tingkat keparahan dan tenggat tindak lanjut.')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('title')->label('Temuan')->wrap()->limit(55),
                TextColumn::make('ownerUnit.name')->label('Unit'),
                TextColumn::make('source_score')->label('Skor')->numeric(decimalPlaces: 1),
                TextColumn::make('severity')->label('Prioritas')->badge()->color(fn (string $state): string => match ($state) {
                    'critical' => 'danger',
                    'high' => 'warning',
                    'medium' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('state')->label('Status')->badge(),
                TextColumn::make('due_on')->label('Tenggat')->date('d M Y')->color(fn (Finding $record): string => Carbon::parse($record->due_on)->isPast() ? 'danger' : 'gray'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada temuan prioritas');
    }
}
