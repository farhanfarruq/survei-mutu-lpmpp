<?php

namespace App\Filament\Resources\RespondentGroups;

use App\Filament\Resources\RespondentGroups\Pages\CreateRespondentGroup;
use App\Filament\Resources\RespondentGroups\Pages\EditRespondentGroup;
use App\Filament\Resources\RespondentGroups\Pages\ListRespondentGroups;
use App\Models\OrganizationalUnit;
use App\Models\RespondentGroup;
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
use UnitEnum;

class RespondentGroupResource extends Resource
{
    protected static ?string $model = RespondentGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Kelompok Responden';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $schema->components([
            Select::make('organizational_unit_id')->label('Unit')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))->required()->searchable(),
            TextInput::make('code')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama')->required()->maxLength(200),
            Select::make('source_type')->label('Sumber')->options(['manual' => 'Manual', 'import' => 'Import tervalidasi', 'integration' => 'Integrasi'])->default('manual')->required(),
            TextInput::make('schema_version')->label('Versi schema')->default('v1')->required()->maxLength(40),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->label('Nama'), TextColumn::make('organizationalUnit.code')->label('Unit'), TextColumn::make('source_type')->label('Sumber')->badge(), IconColumn::make('is_active')->label('Aktif')->boolean()])->recordActions([EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('organizational_unit_id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()));
    }

    public static function getPages(): array
    {
        return ['index' => ListRespondentGroups::route('/'), 'create' => CreateRespondentGroup::route('/create'), 'edit' => EditRespondentGroup::route('/{record}/edit')];
    }
}
