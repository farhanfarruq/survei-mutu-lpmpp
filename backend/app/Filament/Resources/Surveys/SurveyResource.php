<?php

namespace App\Filament\Resources\Surveys;

use App\Enums\InstrumentStatus;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\EditSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\RespondentGroup;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Survey';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $versions = InstrumentVersion::query()->where('status', InstrumentStatus::Approved)->whereHas('template', fn (Builder $query) => $query->whereIn('owner_unit_id', $ids))->with('template')->get()->mapWithKeys(fn (InstrumentVersion $version) => [$version->id => "{$version->template->code} v{$version->versionLabel()}"]);
        $users = User::query()->whereHas('organizationalUnits', fn (Builder $query) => $query->whereIn('organizational_units.id', $ids))->orderBy('name')->pluck('name', 'id');

        return $schema->components([
            Hidden::make('created_by')->default(fn () => auth()->id()),
            Select::make('instrument_version_id')->label('Versi instrumen approved')->options($versions)->required()->searchable(),
            Select::make('survey_period_id')->label('Periode')->options(SurveyPeriod::query()->where('status', 'active')->orderByDesc('starts_on')->pluck('name', 'id'))->required()->searchable(),
            Select::make('owner_unit_id')->label('Unit pemilik')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))->required()->searchable(),
            TextInput::make('code')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama')->required()->maxLength(240),
            Select::make('privacy_mode')->label('Mode privasi')->options(['anonymous' => 'Anonim', 'confidential' => 'Rahasia', 'detached' => 'Detached participation'])->default('anonymous')->required(),
            DateTimePicker::make('opens_at')->label('Buka')->required()->seconds(false),
            DateTimePicker::make('closes_at')->label('Tutup')->required()->seconds(false)->after('opens_at'),
            Select::make('timezone')->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->default('Asia/Jakarta')->required()->searchable(),
            TextInput::make('reporting_threshold')->label('Minimum reporting n')->numeric()->minValue(10)->default(10)->required(),
            Select::make('action_owner_id')->label('Pemilik tindak lanjut')->options($users)->required()->searchable(),
            Textarea::make('privacy_notice')->label('Privacy notice')->required()->rows(3)->columnSpanFull(),
            Repeater::make('targets')->label('Target')->relationship()->schema([
                Select::make('target_type')->label('Jenis')->options(['respondent_group' => 'Kelompok responden', 'organizational_unit' => 'Unit organisasi'])->required(),
                Select::make('respondent_group_id')->label('Kelompok')->options(RespondentGroup::query()->whereIn('organizational_unit_id', $ids)->where('is_active', true)->orderBy('code')->pluck('name', 'id'))->searchable(),
                Select::make('target_unit_id')->label('Unit target')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))->searchable(),
                TextInput::make('eligible_count')->label('Eligible')->numeric()->minValue(0)->default(0)->required(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Survey'), TextEntry::make('code'), TextEntry::make('state')->badge(),
            TextEntry::make('ownerUnit.name')->label('Unit pemilik'), TextEntry::make('instrumentVersion.template.name')->label('Instrumen'),
            TextEntry::make('instrument_version')->label('Versi')->state(fn (Survey $record) => $record->instrumentVersion->versionLabel()),
            TextEntry::make('opens_at')->label('Buka')->dateTime(), TextEntry::make('closes_at')->label('Tutup')->dateTime(),
            TextEntry::make('privacy_mode')->label('Mode privasi')->badge(), TextEntry::make('reporting_threshold')->label('Minimum reporting'),
            TextEntry::make('privacy_notice')->label('Privacy notice')->columnSpanFull(),
            RepeatableEntry::make('targets')->label('Target')->schema([
                TextEntry::make('target_type')->label('Jenis')->badge(), TextEntry::make('respondentGroup.name')->label('Kelompok')->placeholder('—'),
                TextEntry::make('targetUnit.name')->label('Unit')->placeholder('—'), TextEntry::make('eligible_count')->label('Eligible'),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('instrumentVersion.sections')->label('Preview instrumen')->schema([
                TextEntry::make('title')->label('Bagian'),
                RepeatableEntry::make('questions')->label('Pertanyaan')->schema([TextEntry::make('code'), TextEntry::make('item_text')->label('Item')])->columns(2)->columnSpanFull(),
            ])->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable(), TextColumn::make('name')->label('Nama')->searchable(), TextColumn::make('ownerUnit.code')->label('Unit'),
            TextColumn::make('state')->badge()->sortable(), TextColumn::make('opens_at')->label('Buka')->dateTime()->sortable(), TextColumn::make('responses_count')->label('Respons'),
        ])->recordActions([ViewAction::make(), EditAction::make()->visible(fn (Survey $record) => $record->state->configurationEditable())]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('owner_unit_id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()));
    }

    public static function getPages(): array
    {
        return ['index' => ListSurveys::route('/'), 'create' => CreateSurvey::route('/create'), 'view' => ViewSurvey::route('/{record}'), 'edit' => EditSurvey::route('/{record}/edit')];
    }
}
