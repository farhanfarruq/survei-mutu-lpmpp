<?php

namespace App\Filament\Resources\InstrumentVersions;

use App\Filament\Resources\InstrumentVersions\Pages\CreateInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\Pages\EditInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\Pages\ListInstrumentVersions;
use App\Filament\Resources\InstrumentVersions\Pages\ViewInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\InstrumentVersions\RelationManagers\ScalesRelationManager;
use App\Filament\Resources\InstrumentVersions\RelationManagers\SectionsRelationManager;
use App\Models\InstrumentVersion;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InstrumentVersionResource extends Resource
{
    protected static ?string $model = InstrumentVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Daftar Formulir';

    protected static ?string $modelLabel = 'formulir';

    protected static ?string $pluralModelLabel = 'formulir';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $schema->components([
            Hidden::make('created_by')->default(fn () => auth()->id()),
            Select::make('survey_template_id')->label('Template')->options(SurveyTemplate::query()->whereIn('owner_unit_id', $ids)->orderBy('name')->pluck('name', 'id'))->required()->searchable()->disabled(fn (?InstrumentVersion $record) => $record !== null)->dehydrated(fn (?InstrumentVersion $record) => $record === null),
            TextInput::make('major')->numeric()->minValue(0)->required()->default(1)->disabled(fn (?InstrumentVersion $record) => $record !== null)->dehydrated(fn (?InstrumentVersion $record) => $record === null),
            TextInput::make('minor')->numeric()->minValue(0)->required()->default(0)->disabled(fn (?InstrumentVersion $record) => $record !== null)->dehydrated(fn (?InstrumentVersion $record) => $record === null),
            TextInput::make('patch')->numeric()->minValue(0)->required()->default(0)->disabled(fn (?InstrumentVersion $record) => $record !== null)->dehydrated(fn (?InstrumentVersion $record) => $record === null),
            Select::make('comparability_status')->label('Komparabilitas')->options(['pending' => 'Belum ditentukan', 'comparable' => 'Comparable', 'partial' => 'Sebagian comparable', 'not_comparable' => 'Tidak comparable'])->default('pending')->required(),
            Textarea::make('change_reason')->label('Alasan perubahan')->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('template.name')->label('Template'),
            TextEntry::make('version_label')->label('Versi')->state(fn (InstrumentVersion $record) => $record->versionLabel()),
            TextEntry::make('status')->badge(),
            TextEntry::make('comparability_status')->label('Komparabilitas')->badge(),
            TextEntry::make('change_reason')->label('Alasan perubahan')->columnSpanFull(),
            RepeatableEntry::make('sections')->label('Preview kuesioner')->schema([
                TextEntry::make('title')->label('Bagian'),
                TextEntry::make('description')->label('Petunjuk'),
                RepeatableEntry::make('questions')->label('Pertanyaan')->schema([
                    TextEntry::make('code')->label('Kode'),
                    TextEntry::make('item_text')->label('Item'),
                    TextEntry::make('response_type')->label('Jenis jawaban')->badge(),
                    TextEntry::make('scale.name')->label('Skala')->placeholder('Tidak memakai skala'),
                ])->columns(2)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('template.code')->label('Template')->searchable(),
            TextColumn::make('version')->label('Versi')->state(fn (InstrumentVersion $record) => $record->versionLabel()),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('comparability_status')->label('Komparabilitas')->badge(),
            TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
        ])->recordActions([ViewAction::make(), EditAction::make()->visible(fn (InstrumentVersion $record) => $record->isEditable())]);
    }

    public static function getRelations(): array
    {
        return [CategoriesRelationManager::class, ScalesRelationManager::class, SectionsRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return parent::getEloquentQuery()
            ->whereHas('template', fn (Builder $query) => $query->whereIn('owner_unit_id', $ids));
    }

    public static function getPages(): array
    {
        return ['index' => ListInstrumentVersions::route('/'), 'create' => CreateInstrumentVersion::route('/create'), 'view' => ViewInstrumentVersion::route('/{record}'), 'edit' => EditInstrumentVersion::route('/{record}/edit')];
    }
}
