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
use App\Models\QuestionBankEntry;
use App\Models\QuestionOption;
use App\Models\Scale;
use App\Models\SurveyTemplate;
use App\Services\OrganizationalScope;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
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

    protected static ?string $slug = 'buat-formulir/{record?}';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.create-survey-form';

    protected Width|string|null $maxContentWidth = Width::Full;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $record = null;

    public function mount(?string $record = null): void
    {
        $this->record = $record;

        if (! $record) {
            $this->form->fill();

            return;
        }

        $version = $this->version();
        abort_unless(auth()->user()?->can('update', $version), 403);

        $version->load(['template', 'sections.questions.options', 'sections.questions.indicator.category']);
        /** @var SurveyTemplate $template */
        $template = $version->template;
        $questions = [];
        foreach ($version->sections->sortBy('position') as $sectionModel) {
            /** @var InstrumentSection $section */
            $section = $sectionModel;
            foreach ($section->questions->sortBy('position') as $questionModel) {
                /** @var Question $question */
                $question = $questionModel;
                /** @var Indicator $indicator */
                $indicator = $question->indicator;
                /** @var Category $category */
                $category = $indicator->category;
                $options = [];
                foreach ($question->options->sortBy('position') as $optionModel) {
                    /** @var QuestionOption $option */
                    $option = $optionModel;
                    $options[] = ['label' => $option->label];
                }

                $questions[] = [
                    'question_bank_entry_id' => $question->question_bank_entry_id,
                    'item_text' => $question->item_text,
                    'response_type' => $question->response_type,
                    'category_name' => $category->name,
                    'indicator_name' => $indicator->name,
                    'is_required' => $question->is_required,
                    'help_text' => $question->help_text,
                    'options' => $options,
                ];
            }
        }
        $this->form->fill([
            'owner_unit_id' => $template->owner_unit_id,
            'title' => $template->name,
            'description' => $template->purpose,
            'questions' => $questions,
        ]);
        $this->data['questions'] = $questions;
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
                    ->disabled(fn (): bool => $this->isEditing())
                    ->dehydrated()
                    ->searchable()
                    ->columnSpanFull(),
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
                Select::make('bank_question_ids')
                    ->label('Ambil dari Bank Pertanyaan')
                    ->helperText('Pilih satu atau beberapa pertanyaan aktif dari unit formulir.')
                    ->options(fn (Get $get): array => $this->bankQuestionOptions($get('owner_unit_id')))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Actions::make([
                    Action::make('addBankQuestions')
                        ->label('Tambahkan ke formulir')
                        ->icon(Heroicon::OutlinedPlus)
                        ->action(fn () => $this->addBankQuestions()),
                    Action::make('addDefaultBankQuestions')
                        ->label('Tambahkan pertanyaan default')
                        ->icon(Heroicon::OutlinedStar)
                        ->action(fn () => $this->addDefaultBankQuestions()),
                ])->columnSpanFull(),
                Repeater::make('questions')
                    ->label('Pertanyaan')
                    ->helperText('Nomor pertanyaan mengikuti urutan secara otomatis. Gunakan tombol naik/turun untuk mengubah urutan.')
                    ->schema([
                        Hidden::make('question_bank_entry_id'),
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
                    ->collapsible()
                    ->addActionLabel('Tambah pertanyaan')
                    ->itemLabel(fn (array $state, int $index): string => 'Pertanyaan '.($index + 1).(filled($state['item_text'] ?? null) ? ' — '.Str::limit($state['item_text'], 55) : ' — Belum diisi'))
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function isEditing(): bool
    {
        return filled($this->record);
    }

    /** @return array<string, string> */
    private function bankQuestionOptions(?string $ownerUnitId): array
    {
        if (blank($ownerUnitId) || ! app(OrganizationalScope::class)->allows(auth()->user(), $ownerUnitId)) {
            return [];
        }

        return QuestionBankEntry::query()
            ->where('owner_unit_id', $ownerUnitId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('category_label')
            ->orderBy('item_text')
            ->get()
            ->mapWithKeys(fn (QuestionBankEntry $entry): array => [
                $entry->id => ($entry->is_default ? 'Default · ' : '').$entry->item_text,
            ])->all();
    }

    public function addBankQuestions(): void
    {
        $this->appendBankQuestions($this->data['bank_question_ids'] ?? []);
        $this->data['bank_question_ids'] = [];
    }

    public function addDefaultBankQuestions(): void
    {
        $ownerUnitId = $this->data['owner_unit_id'] ?? null;
        if (blank($ownerUnitId) || ! app(OrganizationalScope::class)->allows(auth()->user(), $ownerUnitId)) {
            Notification::make()->title('Pilih unit formulir terlebih dahulu')->warning()->send();

            return;
        }

        $ids = QuestionBankEntry::query()
            ->where('owner_unit_id', $ownerUnitId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('category_label')
            ->orderBy('item_text')
            ->pluck('id')
            ->all();

        $this->appendBankQuestions($ids);
    }

    /** @param array<int, string> $ids */
    private function appendBankQuestions(array $ids): void
    {
        $ownerUnitId = $this->data['owner_unit_id'] ?? null;
        $questions = collect($this->data['questions'] ?? [])->reject(fn (array $question): bool => blank($question['item_text'] ?? null))->values();
        $existingIds = $questions->pluck('question_bank_entry_id')->filter();
        $this->data['questions'] = $questions->all();
        $entries = QuestionBankEntry::query()
            ->where('owner_unit_id', $ownerUnitId)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->reject(fn (QuestionBankEntry $entry): bool => $existingIds->contains($entry->id));

        if ($entries->isEmpty()) {
            Notification::make()->title('Tidak ada pertanyaan baru yang dapat ditambahkan')->warning()->send();

            return;
        }

        foreach ($entries as $entry) {
            $this->data['questions'][] = [
                'question_bank_entry_id' => $entry->id,
                'item_text' => $entry->item_text,
                'response_type' => $entry->response_type,
                'category_name' => $entry->category_label,
                'indicator_name' => $entry->indicator_label,
                'is_required' => true,
                'help_text' => $entry->help_text,
                'options' => self::bankOptions($entry),
            ];
        }

        Notification::make()->title($entries->count().' pertanyaan ditambahkan')->success()->send();
    }

    /** @return array<int, array{label: string}> */
    private static function bankOptions(QuestionBankEntry $entry): array
    {
        $defaultOptions = $entry->getAttribute('default_options');
        if (! is_array($defaultOptions)) {
            return [];
        }

        return collect($defaultOptions)->map(fn (mixed $option): array => [
            'label' => is_array($option) ? (string) ($option['label'] ?? '') : (string) $option,
        ])->all();
    }

    private function version(): InstrumentVersion
    {
        return InstrumentVersion::query()->findOrFail($this->record);
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
        $editing = $this->isEditing();
        $version = $editing ? $this->version() : null;

        abort_unless(
            $user
            && static::canAccess()
            && app(OrganizationalScope::class)->allows($user, $data['owner_unit_id']),
            403,
        );
        if ($version) {
            abort_unless($user->can('update', $version), 403);
        }

        $existingBankIds = collect();
        if ($version) {
            foreach ($version->sections()->with('questions')->get() as $sectionModel) {
                /** @var InstrumentSection $section */
                $section = $sectionModel;
                $existingBankIds->push(...$section->questions->pluck('question_bank_entry_id')->filter());
            }
        }

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

            $bankId = $question['question_bank_entry_id'] ?? null;
            if ($bankId && ! $existingBankIds->contains($bankId)) {
                $available = QuestionBankEntry::query()
                    ->whereKey($bankId)
                    ->where('owner_unit_id', $data['owner_unit_id'])
                    ->where('is_active', true)
                    ->exists();
                if (! $available) {
                    throw ValidationException::withMessages([
                        "data.questions.{$index}.item_text" => 'Pertanyaan bank ini sudah nonaktif atau tidak tersedia untuk unit formulir.',
                    ]);
                }
            }
        }
        unset($question);

        $version = DB::transaction(function () use ($data, $user, $version): InstrumentVersion {
            if ($version) {
                $version->template->update([
                    'name' => $data['title'],
                    'purpose' => filled($data['description'] ?? null) ? $data['description'] : 'Formulir survei '.$data['title'],
                ]);
                $this->writeInstrumentContent($version, $data);
                $version->forceFill(['content_hash' => null])->saveQuietly();

                activity('instrument')
                    ->performedOn($version)
                    ->causedBy($user)
                    ->event('builder_updated')
                    ->withProperties(['question_count' => count($data['questions'])])
                    ->log('Formulir diperbarui melalui editor sederhana');

                return $version;
            }

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
            $this->writeInstrumentContent($version, $data);

            activity('instrument')
                ->performedOn($version)
                ->causedBy($user)
                ->event('builder_created')
                ->withProperties(['question_count' => count($data['questions'])])
                ->log('Formulir dibuat melalui editor sederhana');

            return $version;
        });

        Notification::make()
            ->title($editing ? 'Formulir berhasil diperbarui' : 'Formulir berhasil dibuat')
            ->body('Formulir disimpan sebagai draf. Anda bisa memeriksa dan mengajukannya untuk disetujui.')
            ->success()
            ->send();

        $this->redirect(InstrumentVersionResource::getUrl('view', ['record' => $version]));
    }

    /** @param array<string, mixed> $data */
    private function writeInstrumentContent(InstrumentVersion $version, array $data): void
    {
        $version->sections()->delete();
        $version->categories()->delete();
        $version->scales()->delete();

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
                'question_bank_entry_id' => $question['question_bank_entry_id'] ?? null,
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
    }
}
