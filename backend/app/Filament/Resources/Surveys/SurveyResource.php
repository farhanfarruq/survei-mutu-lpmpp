<?php

namespace App\Filament\Resources\Surveys;

use App\Enums\InstrumentStatus;
use App\Enums\SurveyState;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\EditSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\RespondentGroup;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Kelola Survei';

    protected static ?string $modelLabel = 'survei';

    protected static ?string $pluralModelLabel = 'survei';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());
        $versions = InstrumentVersion::query()
            ->where('status', InstrumentStatus::Approved)
            ->whereHas('template', fn (Builder $query) => $query->whereIn('owner_unit_id', $ids))
            ->with('template')
            ->get()
            ->mapWithKeys(function (InstrumentVersion $version): array {
                /** @var SurveyTemplate $template */
                $template = $version->template;

                return [$version->id => $template->name];
            });

        return $schema->components([
            Section::make('Identitas survei')
                ->description('Pilih formulir, periode, dan unit penanggung jawab.')
                ->schema([
                    Hidden::make('created_by')->default(fn () => auth()->id()),
                    Hidden::make('code')->default(fn (): string => 'SURVEI-'.Str::upper(Str::uuid()->toString())),
                    Hidden::make('privacy_mode')->default('anonymous'),
                    Hidden::make('timezone')->default('Asia/Jakarta'),
                    Hidden::make('reporting_threshold')->default(10),
                    Hidden::make('action_owner_id')->default(fn () => auth()->id()),
                    Hidden::make('privacy_notice')->default('Jawaban disimpan secara anonim dan hanya ditampilkan dalam bentuk ringkasan.'),
                    Select::make('instrument_version_id')->label('Formulir yang digunakan')->options($versions)->required()->searchable()->helperText('Hanya formulir yang sudah disetujui yang dapat dipilih.'),
                    Select::make('survey_period_id')->label('Periode pelaksanaan')->options(SurveyPeriod::query()->where('status', 'active')->orderByDesc('starts_on')->pluck('name', 'id'))->required()->searchable(),
                    Select::make('owner_unit_id')->label('Unit penanggung jawab')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id'))->required()->searchable(),
                    TextInput::make('name')->label('Nama survei')->placeholder('Contoh: Survei Kepuasan Mahasiswa 2026')->required()->maxLength(240)->columnSpanFull(),
                ])->columns(2),
            Section::make('Jadwal pelaksanaan')
                ->description('Gunakan Waktu Indonesia Barat (WIB).')
                ->schema([
                    DateTimePicker::make('opens_at')->label('Mulai pengisian')->timezone('Asia/Jakarta')->helperText('Waktu Indonesia Barat (WIB).')->required()->seconds(false),
                    DateTimePicker::make('closes_at')->label('Batas akhir pengisian')->timezone('Asia/Jakarta')->helperText('Waktu Indonesia Barat (WIB).')->required()->seconds(false)->after('opens_at'),
                ])->columns(2),
            Section::make('Sasaran responden')
                ->description('Pilih unit atau kelompok yang akan menerima survei.')
                ->schema([
                    Repeater::make('targets')->label('Siapa yang akan mengisi?')->relationship()->schema([
                        /* */ Select::make('target_type')->label('Sasaran responden')->options(['organizational_unit' => 'Unit beserta seluruh unit di bawahnya', 'respondent_group' => 'Kelompok responden khusus'])->default('organizational_unit')->live(),
                        Select::make('respondent_group_id')->label('Pilih kelompok responden')->options(RespondentGroup::query()->whereIn('organizational_unit_id', $ids)->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->visible(fn (Get $get): bool => $get('target_type') === 'respondent_group')->required(fn (Get $get): bool => $get('target_type') === 'respondent_group')->dehydratedWhenHidden(),
                        Select::make('target_unit_id')->label('Pilih unit/program studi')->helperText('Memilih institut atau fakultas otomatis mencakup seluruh program studi di bawahnya.')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id'))->searchable()->visible(fn (Get $get): bool => $get('target_type') === 'organizational_unit')->required(fn (Get $get): bool => $get('target_type') === 'organizational_unit')->dehydratedWhenHidden(),
                        TextInput::make('eligible_count')->label('Perkiraan jumlah responden')->numeric()->minValue(0)->default(0)->required(),
                    ])->defaultItems(1)->minItems(1)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::normalizeTarget($data))
                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::normalizeTarget($data))
                        ->addActionLabel('Tambah kelompok sasaran')->columns(2)->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama survei'),
            TextEntry::make('state')->label('Status')->badge()->formatStateUsing(fn (SurveyState|string $state): string => self::statusLabel($state)),
            TextEntry::make('ownerUnit.name')->label('Unit penanggung jawab'),
            TextEntry::make('instrumentVersion.template.name')->label('Formulir'),
            TextEntry::make('opens_at')->label('Mulai pengisian')->dateTime()->timezone('Asia/Jakarta'),
            TextEntry::make('closes_at')->label('Batas akhir')->dateTime()->timezone('Asia/Jakarta'),
            TextEntry::make('code')->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextEntry::make('instrument_version')->label('Versi')->state(fn (Survey $record) => $record->instrumentVersion->versionLabel())->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextEntry::make('privacy_mode')->label('Mode privasi')->badge()->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextEntry::make('reporting_threshold')->label('Batas minimum laporan')->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            RepeatableEntry::make('targets')->label('Kelompok sasaran')->schema([
                TextEntry::make('respondentGroup.name')->label('Kelompok')->placeholder('—'),
                TextEntry::make('targetUnit.name')->label('Unit')->placeholder('—'),
                TextEntry::make('eligible_count')->label('Perkiraan responden'),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('instrumentVersion.sections')->label('Isi formulir')->schema([
                TextEntry::make('title')->label('Bagian'),
                RepeatableEntry::make('questions')->label('Pertanyaan')->schema([TextEntry::make('item_text')->label('Pertanyaan')])->columnSpanFull(),
            ])->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama survei')->searchable()->sortable(),
            TextColumn::make('ownerUnit.name')->label('Unit'),
            TextColumn::make('state')->label('Status')->badge()->sortable()->formatStateUsing(fn (SurveyState|string $state): string => self::statusLabel($state)),
            TextColumn::make('opens_at')->label('Mulai')->dateTime()->timezone('Asia/Jakarta')->sortable(),
            TextColumn::make('responses_count')->label('Jawaban')->numeric()->sortable(),
            TextColumn::make('code')->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
        ])->recordActions([
            EditAction::make()->label('Lanjutkan edit')->visible(fn (Survey $record): bool => auth()->user()->can('update', $record)),
            ViewAction::make()->label(fn (Survey $record): string => match ($record->getRawOriginal('state')) {
                'draft', 'returned' => 'Periksa & ajukan',
                'approved' => 'Periksa & publikasikan',
                'scheduled', 'active' => 'Kelola jadwal',
                default => 'Lihat',
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('owner_unit_id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()));
    }

    public static function getPages(): array
    {
        return ['index' => ListSurveys::route('/'), 'create' => CreateSurvey::route('/create'), 'view' => ViewSurvey::route('/{record}'), 'edit' => EditSurvey::route('/{record}/edit')];
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

    private static function normalizeTarget(array $data): array
    {
        $data['target_type'] ??= 'organizational_unit';
        $data['respondent_group_id'] = $data['target_type'] === 'respondent_group' ? ($data['respondent_group_id'] ?? null) : null;
        $data['target_unit_id'] = $data['target_type'] === 'organizational_unit' ? ($data['target_unit_id'] ?? null) : null;

        return $data;
    }
}
