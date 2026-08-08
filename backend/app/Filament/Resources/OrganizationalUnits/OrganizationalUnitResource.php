<?php

namespace App\Filament\Resources\OrganizationalUnits;

use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\ListOrganizationalUnits;
use App\Models\OrganizationalUnit;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationalUnitResource extends Resource
{
    protected static ?string $model = OrganizationalUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Unit Organisasi';

    protected static ?string $modelLabel = 'unit organisasi';

    protected static ?string $pluralModelLabel = 'unit organisasi';

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $allowedIds = $user ? app(OrganizationalScope::class)->accessibleUnitIds($user) : collect();

        return $schema->components([
            Select::make('parent_id')
                ->label('Unit induk')
                ->options(OrganizationalUnit::query()->whereIn('id', $allowedIds)->orderBy('code')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            TextInput::make('code')->label('Kode')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama')->required()->maxLength(160),
            Select::make('type')->label('Tipe')->options([
                'university' => 'Perguruan tinggi',
                'faculty' => 'Fakultas',
                'program' => 'Program studi',
                'unit' => 'Unit kerja',
                'faculty_or_unit' => 'Fakultas/unit (fixture)',
            ])->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kode')->searchable()->sortable(),
            TextColumn::make('name')->label('Nama')->searchable(),
            TextColumn::make('type')->label('Tipe')->badge(),
            TextColumn::make('parent.code')->label('Induk')->placeholder('—'),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('parent');
        $user = auth()->user();

        return $user
            ? $query->whereIn('id', app(OrganizationalScope::class)->accessibleUnitIds($user))
            : $query->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizationalUnits::route('/'),
            'create' => CreateOrganizationalUnit::route('/create'),
            'edit' => EditOrganizationalUnit::route('/{record}/edit'),
        ];
    }
}
