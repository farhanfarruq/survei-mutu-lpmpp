<?php

namespace App\Filament\Resources\SurveyTemplates;

use App\Filament\Resources\SurveyTemplates\Pages\CreateSurveyTemplate;
use App\Filament\Resources\SurveyTemplates\Pages\EditSurveyTemplate;
use App\Filament\Resources\SurveyTemplates\Pages\ListSurveyTemplates;
use App\Models\OrganizationalUnit;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SurveyTemplateResource extends Resource
{
    protected static ?string $model = SurveyTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Template Survei';

    protected static ?string $modelLabel = 'template survei';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $schema->components([
            Hidden::make('created_by')->default(fn () => auth()->id()),
            Select::make('owner_unit_id')->label('Unit pemilik')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))->required()->searchable(),
            TextInput::make('code')->label('Kode')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('family_code')->label('Keluarga survei')->required()->maxLength(80),
            TextInput::make('name')->label('Nama')->required()->maxLength(240),
            Select::make('status')->options(['active' => 'Aktif', 'retired' => 'Retired'])->default('active')->required(),
            Textarea::make('purpose')->label('Tujuan')->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->label('Nama')->searchable(),
            TextColumn::make('ownerUnit.code')->label('Unit')->sortable(),
            TextColumn::make('family_code')->label('Keluarga'),
            TextColumn::make('status')->badge(),
            TextColumn::make('versions_count')->counts('versions')->label('Versi'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('owner_unit_id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()));
    }

    public static function getPages(): array
    {
        return ['index' => ListSurveyTemplates::route('/'), 'create' => CreateSurveyTemplate::route('/create'), 'edit' => EditSurveyTemplate::route('/{record}/edit')];
    }
}
