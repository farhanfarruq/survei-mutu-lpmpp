<?php

namespace App\Filament\Resources\QuestionBankEntries;

use App\Filament\Resources\QuestionBankEntries\Pages\CreateQuestionBankEntry;
use App\Filament\Resources\QuestionBankEntries\Pages\EditQuestionBankEntry;
use App\Filament\Resources\QuestionBankEntries\Pages\ListQuestionBankEntries;
use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class QuestionBankEntryResource extends Resource
{
    protected static ?string $model = QuestionBankEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Bank Pertanyaan';

    protected static ?string $modelLabel = 'bank pertanyaan';

    public static function form(Schema $schema): Schema
    {
        $ids = app(OrganizationalScope::class)->accessibleUnitIds(auth()->user());

        return $schema->components([
            Hidden::make('created_by')->default(fn () => auth()->id()),
            Hidden::make('code'),
            Hidden::make('family_code'),
            Section::make('Pertanyaan')
                ->description('Cukup isi pertanyaan dan bentuk jawaban yang dibutuhkan.')
                ->schema([
                    Select::make('owner_unit_id')
                        ->label('Unit pemilik')
                        ->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))
                        ->default(fn () => auth()->user()?->organizationalUnits()->wherePivot('is_primary', true)->value('organizational_units.id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('category_label')
                        ->label('Kategori')
                        ->placeholder('Contoh: Pelayanan akademik')
                        ->default('Umum')
                        ->helperText('Boleh dibiarkan Umum jika belum memerlukan kategori khusus.')
                        ->maxLength(200),
                    Textarea::make('item_text')
                        ->label('Tulis pertanyaan')
                        ->placeholder('Contoh: Seberapa puas Anda terhadap pelayanan akademik?')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('response_type')
                        ->label('Jenis jawaban')
                        ->options([
                            'scale' => 'Skala kepuasan 1–5',
                            'single_choice' => 'Pilih satu jawaban',
                            'multiple_choice' => 'Pilih beberapa jawaban',
                            'short_text' => 'Jawaban singkat',
                            'long_text' => 'Jawaban panjang',
                            'number' => 'Angka',
                        ])
                        ->required()
                        ->live(),
                ])->columns(2),
            Section::make('Pilihan jawaban')
                ->description('Tulis jawaban yang akan dilihat responden. Kode pilihan dibuat otomatis.')
                ->visible(fn (Get $get): bool => in_array($get('response_type'), ['single_choice', 'multiple_choice'], true))
                ->schema([
                    Repeater::make('default_options')
                        ->label('Daftar pilihan')
                        ->schema([
                            Hidden::make('code'),
                            Hidden::make('score_value'),
                            Hidden::make('is_exclusive'),
                            TextInput::make('label')
                                ->label('Teks pilihan')
                                ->placeholder('Contoh: Sangat puas')
                                ->required()
                                ->maxLength(300),
                        ])
                        ->defaultItems(2)
                        ->minItems(2)
                        ->addActionLabel('Tambah pilihan jawaban')
                        ->columnSpanFull(),
                ]),
            Section::make('Status penggunaan')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif dan dapat digunakan')
                        ->default(true),
                    Toggle::make('is_default')
                        ->label('Pertanyaan default')
                        ->helperText('Dapat ditambahkan sekaligus dari editor formulir.'),
                ])->columns(2),
            Section::make('Pengaturan lanjutan')
                ->description('Opsional. Buka hanya jika memerlukan klasifikasi atau metode pengukuran khusus.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('indicator_label')
                        ->label('Indikator')
                        ->placeholder('Contoh: Kecepatan pelayanan')
                        ->default('Jawaban responden')
                        ->maxLength(200),
                    Select::make('method')
                        ->label('Metode pengukuran')
                        ->options(['internal' => 'Internal', 'SERVPERF' => 'SERVPERF', 'SERVQUAL' => 'SERVQUAL', 'IPA' => 'IPA', 'CSI' => 'CSI', 'SKM' => 'SKM/IKM', 'NPS' => 'NPS'])
                        ->default('internal'),
                    Textarea::make('help_text')
                        ->label('Petunjuk tambahan')
                        ->placeholder('Opsional, ditampilkan untuk membantu responden.')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('measurement_purpose')
                        ->label('Tujuan pengukuran')
                        ->placeholder('Kosongkan untuk menggunakan tujuan standar.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $data): array
    {
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : 'QB-'.Str::upper((string) Str::ulid());
        $data['family_code'] = filled($data['family_code'] ?? null) ? $data['family_code'] : $data['code'];
        $data['method'] = filled($data['method'] ?? null) ? $data['method'] : 'internal';
        $data['category_label'] = trim((string) ($data['category_label'] ?? '')) ?: 'Umum';
        $data['indicator_label'] = trim((string) ($data['indicator_label'] ?? '')) ?: 'Jawaban responden';
        $data['measurement_purpose'] = trim((string) ($data['measurement_purpose'] ?? ''))
            ?: "Mengukur {$data['indicator_label']} pada kategori {$data['category_label']}.";

        if (! in_array($data['response_type'] ?? null, ['single_choice', 'multiple_choice'], true)) {
            $data['default_options'] = null;

            return $data;
        }

        $data['default_options'] = collect($data['default_options'] ?? [])
            ->filter(fn (array $option): bool => filled($option['label'] ?? null))
            ->values()
            ->map(fn (array $option, int $index): array => [
                'code' => filled($option['code'] ?? null) ? $option['code'] : 'O'.($index + 1),
                'label' => trim((string) $option['label']),
                'score_value' => $option['score_value'] ?? null,
                'is_exclusive' => (bool) ($option['is_exclusive'] ?? false),
            ])->all();

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('item_text')->label('Pertanyaan')->limit(70)->searchable(),
            TextColumn::make('category_label')->label('Kategori')->searchable(),
            TextColumn::make('response_type')->label('Jenis jawaban')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'scale' => 'Skala 1–5',
                'single_choice' => 'Pilih satu',
                'multiple_choice' => 'Pilih beberapa',
                'short_text' => 'Jawaban singkat',
                'long_text' => 'Jawaban panjang',
                'number' => 'Angka',
                default => $state,
            }),
            TextColumn::make('ownerUnit.code')->label('Unit'),
            IconColumn::make('is_default')->label('Default')->boolean(),
            ToggleColumn::make('is_active')->label('Aktif')->disabled(fn (QuestionBankEntry $record): bool => ! auth()->user()->can('update', $record)),
        ])->filters([
            TernaryFilter::make('is_active')->label('Status aktif')->trueLabel('Aktif')->falseLabel('Nonaktif'),
            TernaryFilter::make('is_default')->label('Pertanyaan default')->trueLabel('Default')->falseLabel('Bukan default'),
        ])->recordActions([
            EditAction::make()->visible(fn (QuestionBankEntry $record): bool => auth()->user()->can('update', $record)),
            Action::make('duplicate')
                ->label('Salin')
                ->icon(Heroicon::OutlinedSquare2Stack)
                ->visible(fn (): bool => auth()->user()->can('create', QuestionBankEntry::class))
                ->action(function (QuestionBankEntry $record): void {
                    $copy = $record->replicate(['code', 'created_by', 'is_default']);
                    $copy->forceFill([
                        'code' => Str::limit($record->code, 65, '').'-SALINAN-'.Str::upper(Str::random(6)),
                        'created_by' => auth()->id(),
                        'is_default' => false,
                    ])->save();

                    Notification::make()->title('Pertanyaan berhasil disalin')->success()->send();
                }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('owner_unit_id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()));
    }

    public static function getPages(): array
    {
        return ['index' => ListQuestionBankEntries::route('/'), 'create' => CreateQuestionBankEntry::route('/create'), 'edit' => EditQuestionBankEntry::route('/{record}/edit')];
    }
}
