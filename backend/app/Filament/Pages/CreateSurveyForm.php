<?php

namespace App\Filament\Pages;

use App\Enums\InstrumentStatus;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Models\Category;
use App\Models\Indicator;
use App\Models\InstrumentSection;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\Question;
use App\Models\Scale;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** @property Schema $form */
class CreateSurveyForm extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Buat Formulir';

    protected static ?string $title = 'Buat Formulir Survei';

    protected static ?string $slug = 'buat-formulir';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.create-survey-form';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getHeading(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->can('create', SurveyTemplate::class)
            && $user->can('create', InstrumentVersion::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('audience_preset')
                    ->label('Mulai dari contoh')
                    ->options([
                        'mahasiswa' => 'Survei mahasiswa',
                        'dosen' => 'Survei dosen',
                        'tenaga_kependidikan' => 'Survei tenaga kependidikan',
                        'alumni' => 'Survei alumni',
                        'stakeholder' => 'Survei stakeholder',
                    ])
                    ->placeholder('Formulir kosong')
                    ->helperText('Memilih contoh akan mengisi pertanyaan awal yang masih bisa diubah.')
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('questions', self::presetQuestions($state)))
                    ->columnSpanFull(),
                Select::make('owner_unit_id')
                    ->label('Unit pemilik')
                    ->options(fn () => OrganizationalUnit::query()
                        ->whereIn('id', app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->default(fn () => auth()->user()->organizationalUnits()
                        ->wherePivot('is_primary', true)
                        ->value('organizational_units.id'))
                    ->required()
                    ->searchable(),
                TextInput::make('title')
                    ->label('Judul formulir')
                    ->placeholder('Contoh: Survei Kepuasan Layanan Akademik')
                    ->required()
                    ->live(debounce: 300)
                    ->maxLength(240)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->placeholder('Jelaskan tujuan survei atau petunjuk pengisian.')
                    ->live(debounce: 300)
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('questions')
                    ->label('Pertanyaan')
                    ->schema([
                        Textarea::make('item_text')
                            ->label('Tulis pertanyaan')
                            ->placeholder('Contoh: Seberapa puas Anda dengan layanan kami?')
                            ->required()
                            ->live(debounce: 300)
                            ->rows(2)
                            ->columnSpanFull(),
                        Select::make('response_type')
                            ->label('Jenis jawaban')
                            ->options([
                                'scale' => 'Skala kepuasan 1–5',
                                'yes_no' => 'Ya / Tidak',
                                'short_text' => 'Jawaban singkat',
                                'long_text' => 'Jawaban panjang',
                                'single_choice' => 'Pilih satu jawaban',
                                'multiple_choice' => 'Pilih beberapa jawaban',
                                'number' => 'Angka',
                            ])
                            ->default('short_text')
                            ->required()
                            ->live(),
                        TextInput::make('category_name')
                            ->label('Kategori')
                            ->placeholder('Contoh: Pelayanan akademik')
                            ->default('Umum')
                            ->helperText('Kosongkan jika pertanyaan termasuk kategori Umum.')
                            ->maxLength(160),
                        TextInput::make('indicator_name')
                            ->label('Indikator')
                            ->placeholder('Contoh: Kecepatan pelayanan')
                            ->default('Jawaban responden')
                            ->helperText('Kosongkan jika belum memerlukan indikator khusus.')
                            ->maxLength(160),
                        Toggle::make('is_required')
                            ->label('Wajib diisi')
                            ->live()
                            ->default(true),
                        Textarea::make('help_text')
                            ->label('Petunjuk tambahan')
                            ->placeholder('Opsional')
                            ->live(debounce: 300)
                            ->rows(2)
                            ->columnSpanFull(),
                        Repeater::make('options')
                            ->label('Pilihan jawaban')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Pilihan')
                                    ->placeholder('Tulis pilihan jawaban')
                                    ->live(debounce: 300)
                                    ->maxLength(300),
                            ])
                            ->defaultItems(2)
                            ->addActionLabel('Tambah pilihan')
                            ->visible(fn (Get $get): bool => in_array($get('response_type'), ['single_choice', 'multiple_choice'], true))
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable()
                    ->reorderableWithDragAndDrop(false)
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->itemNumbers()
                    ->addActionLabel('Tambah pertanyaan')
                    ->itemLabel(fn (array $state): string => filled($state['item_text'] ?? null) ? Str::limit($state['item_text'], 70) : 'Pertanyaan baru')
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    /** @return array<int, array<string, mixed>> */
    private static function presetQuestions(?string $preset): array
    {
        $subject = [
            'mahasiswa' => 'layanan akademik bagi mahasiswa',
            'dosen' => 'dukungan pelaksanaan pengajaran bagi dosen',
            'tenaga_kependidikan' => 'dukungan kerja bagi tenaga kependidikan',
            'alumni' => 'layanan alumni',
            'stakeholder' => 'layanan kerja sama bagi stakeholder',
        ][$preset] ?? null;

        if ($subject === null) {
            return [];
        }

        return [
            [
                'item_text' => "Seberapa puas Anda terhadap {$subject}?",
                'response_type' => 'scale',
                'category_name' => 'Kualitas layanan',
                'indicator_name' => 'Kepuasan',
                'is_required' => true,
            ],
            [
                'item_text' => "Apakah informasi mengenai {$subject} mudah ditemukan?",
                'response_type' => 'yes_no',
                'category_name' => 'Informasi',
                'indicator_name' => 'Kemudahan akses',
                'is_required' => true,
            ],
            [
                'item_text' => 'Apa saran utama Anda untuk perbaikan layanan?',
                'response_type' => 'long_text',
                'category_name' => 'Saran',
                'indicator_name' => 'Masukan perbaikan',
                'is_required' => false,
            ],
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        abort_unless(
            $user
            && static::canAccess()
            && app(OrganizationalScope::class)->allows($user, $data['owner_unit_id']),
            403,
        );

        foreach ($data['questions'] as $index => &$question) {
            $question['category_name'] = trim((string) ($question['category_name'] ?? '')) ?: 'Umum';
            $question['indicator_name'] = trim((string) ($question['indicator_name'] ?? '')) ?: 'Jawaban responden';
            $question['options'] = collect($question['options'] ?? [])
                ->pluck('label')
                ->filter(fn (?string $label): bool => filled($label))
                ->values()
                ->all();

            if ($question['response_type'] === 'yes_no') {
                $question['response_type'] = 'single_choice';
                $question['options'] = ['Ya', 'Tidak'];
            }

            if (in_array($question['response_type'], ['single_choice', 'multiple_choice'], true)
                && count($question['options']) < 2) {
                throw ValidationException::withMessages([
                    "data.questions.{$index}.options" => 'Tambahkan minimal dua pilihan jawaban.',
                ]);
            }
        }
        unset($question);

        $version = DB::transaction(function () use ($data, $user): InstrumentVersion {
            $code = 'FORM-'.Str::upper(Str::uuid()->toString());
            $description = filled($data['description'] ?? null)
                ? $data['description']
                : 'Formulir survei '.$data['title'];

            $template = SurveyTemplate::query()->create([
                'owner_unit_id' => $data['owner_unit_id'],
                'code' => $code,
                'family_code' => $code,
                'name' => $data['title'],
                'status' => 'active',
                'purpose' => $description,
                'created_by' => $user->id,
            ]);

            /** @var InstrumentVersion $version */
            $version = $template->versions()->create([
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'status' => InstrumentStatus::Draft,
                'comparability_status' => 'pending',
                'change_reason' => 'Pembuatan formulir awal.',
                'created_by' => $user->id,
            ]);

            /** @var Scale $scale */
            $scale = $version->scales()->create([
                'code' => 'KEPUASAN-1-5',
                'name' => 'Skala kepuasan 1–5',
                'scale_type' => 'likert',
                'min_value' => 1,
                'max_value' => 5,
                'na_allowed' => false,
                'missing_policy' => 'exclude_item',
            ]);

            foreach ([1 => 'Sangat tidak puas', 'Tidak puas', 'Cukup', 'Puas', 'Sangat puas'] as $value => $label) {
                $scale->points()->create([
                    'code' => (string) $value,
                    'numeric_value' => $value,
                    'label' => $label,
                    'position' => $value,
                    'is_na' => false,
                    'is_neutral' => $value === 3,
                ]);
            }

            /** @var InstrumentSection $section */
            $section = $version->sections()->create([
                'code' => 'BAGIAN-1',
                'title' => 'Pertanyaan',
                'description' => $data['description'] ?? null,
                'position' => 1,
            ]);

            /** @var array<string, Category> $categories */
            $categories = [];
            /** @var array<string, Indicator> $indicators */
            $indicators = [];

            foreach ($data['questions'] as $index => $question) {
                $categoryKey = Str::lower($question['category_name']);
                if (! isset($categories[$categoryKey])) {
                    /** @var Category $category */
                    $category = $version->categories()->create([
                        'code' => 'KAT-'.str_pad((string) (count($categories) + 1), 2, '0', STR_PAD_LEFT),
                        'name' => $question['category_name'],
                        'description' => 'Kategori dari formulir sederhana.',
                        'position' => count($categories) + 1,
                    ]);
                    $categories[$categoryKey] = $category;
                }
                $category = $categories[$categoryKey];
                $indicatorKey = $categoryKey.'|'.Str::lower($question['indicator_name']);
                if (! isset($indicators[$indicatorKey])) {
                    /** @var Indicator $indicator */
                    $indicator = $category->indicators()->create([
                        'code' => 'IND-'.str_pad((string) (count($indicators) + 1), 2, '0', STR_PAD_LEFT),
                        'name' => $question['indicator_name'],
                        'construct' => $question['category_name'],
                        'weight' => 1,
                    ]);
                    $indicators[$indicatorKey] = $indicator;
                }
                $indicator = $indicators[$indicatorKey];

                /** @var Question $createdQuestion */
                $createdQuestion = $section->questions()->create([
                    'indicator_id' => $indicator->id,
                    'scale_id' => $question['response_type'] === 'scale' ? $scale->id : null,
                    'code' => 'P'.($index + 1),
                    'item_text' => $question['item_text'],
                    'response_type' => $question['response_type'],
                    'is_required' => $question['is_required'] ?? false,
                    'position' => $index + 1,
                    'help_text' => $question['help_text'] ?? null,
                    'measurement_purpose' => 'Mengumpulkan jawaban responden.',
                    'method' => 'internal',
                ]);

                foreach ($question['options'] as $optionIndex => $label) {
                    $createdQuestion->options()->create([
                        'code' => 'O'.($optionIndex + 1),
                        'label' => $label,
                        'position' => $optionIndex + 1,
                        'is_exclusive' => false,
                    ]);
                }
            }

            return $version;
        });

        Notification::make()
            ->title('Formulir berhasil dibuat')
            ->body('Formulir disimpan sebagai draf. Anda bisa memeriksa dan mengajukannya untuk disetujui.')
            ->success()
            ->send();

        $this->redirect(InstrumentVersionResource::getUrl('view', ['record' => $version]));
    }
}
