<?php

namespace App\Filament\Resources\InstrumentVersions;

use App\Enums\InstrumentStatus;
use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Resources\InstrumentVersions\Pages\CreateInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\Pages\EditInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\Pages\ListInstrumentVersions;
use App\Filament\Resources\InstrumentVersions\Pages\ViewInstrumentVersion;
use App\Filament\Resources\InstrumentVersions\RelationManagers\SectionsRelationManager;
use App\Models\InstrumentVersion;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\Action;
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

    protected static ?string $navigationLabel = 'Formulir Saya';

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
            TextEntry::make('status')->label('Status formulir')->badge()->formatStateUsing(fn (InstrumentStatus|string $state): string => static::statusLabel($state)),
            TextEntry::make('version_label')->label('Versi')->state(fn (InstrumentVersion $record) => $record->versionLabel())->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextEntry::make('comparability_status')->label('Komparabilitas')->badge()->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextEntry::make('change_reason')->label('Catatan perubahan')->columnSpanFull()->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            RepeatableEntry::make('sections')->label('Isi formulir')->schema([
                TextEntry::make('title')->label('Bagian'),
                TextEntry::make('description')->label('Petunjuk'),
                RepeatableEntry::make('questions')->label('Pertanyaan')->schema([
                    TextEntry::make('item_text')->label('Pertanyaan'),
                    TextEntry::make('response_type')->label('Jenis jawaban')->badge()->formatStateUsing(fn (string $state): string => static::responseTypeLabel($state)),
                ])->columns(2)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('template.name')->label('Nama formulir')->searchable()->sortable(),
            TextColumn::make('status')->label('Status')->badge()->sortable()->formatStateUsing(fn (InstrumentStatus|string $state): string => static::statusLabel($state)),
            TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            TextColumn::make('version')->label('Versi')->state(fn (InstrumentVersion $record) => $record->versionLabel())->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            TextColumn::make('comparability_status')->label('Komparabilitas')->badge()->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
        ])->recordActions([
            Action::make('editSimple')
                ->label('Lanjutkan edit')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->url(fn (InstrumentVersion $record): string => CreateSurveyForm::getUrl(['record' => $record]))
                ->visible(fn (InstrumentVersion $record): bool => auth()->user()->can('update', $record)),
            ViewAction::make()->label(function (InstrumentVersion $record): string {
                $status = $record->getRawOriginal('status');

                return match (true) {
                    in_array($status, ['draft', 'returned'], true) && auth()->user()->can('submitReview', $record) => 'Periksa & ajukan',
                    $status === 'approved' && auth()->user()->can('duplicate', $record) => 'Buat versi revisi',
                    default => 'Lihat',
                };
            }),
        ]);
    }

    public static function getRelations(): array
    {
        return [SectionsRelationManager::class];
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

    private static function statusLabel(InstrumentStatus|string $status): string
    {
        $status = $status instanceof InstrumentStatus ? $status->value : $status;

        return match ($status) {
            'draft' => 'Draf',
            'in_review' => 'Menunggu pemeriksaan',
            'returned' => 'Perlu diperbaiki',
            'approved' => 'Disetujui',
            'retired' => 'Tidak digunakan',
            default => $status,
        };
    }

    private static function responseTypeLabel(string $type): string
    {
        return match ($type) {
            'scale' => 'Skala penilaian',
            'single_choice' => 'Pilih satu',
            'multiple_choice' => 'Pilih beberapa',
            'short_text' => 'Jawaban singkat',
            'long_text' => 'Jawaban panjang',
            'number' => 'Angka',
            default => $type,
        };
    }
}
