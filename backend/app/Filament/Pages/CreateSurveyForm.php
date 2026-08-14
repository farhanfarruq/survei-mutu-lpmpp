<?php

namespace App\Filament\Pages;

use App\Enums\InstrumentStatus;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
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
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

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
                                'short_text' => 'Jawaban singkat',
                                'long_text' => 'Jawaban panjang',
                                'single_choice' => 'Pilih satu jawaban',
                                'multiple_choice' => 'Pilih beberapa jawaban',
                                'number' => 'Angka',
                            ])
                            ->default('short_text')
                            ->required()
                            ->live(),
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
            $question['options'] = collect($question['options'] ?? [])
                ->pluck('label')
                ->filter(fn (?string $label): bool => filled($label))
                ->values()
                ->all();

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

            $version = $template->versions()->create([
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'status' => InstrumentStatus::Draft,
                'comparability_status' => 'pending',
                'change_reason' => 'Pembuatan formulir awal.',
                'created_by' => $user->id,
            ]);

            $category = $version->categories()->create([
                'code' => 'UMUM',
                'name' => 'Umum',
                'description' => 'Pertanyaan umum dari formulir sederhana.',
                'position' => 1,
            ]);

            $indicator = $category->indicators()->create([
                'code' => 'JAWABAN',
                'name' => 'Jawaban responden',
                'construct' => 'Umpan balik umum',
                'weight' => 1,
            ]);

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

            $section = $version->sections()->create([
                'code' => 'BAGIAN-1',
                'title' => 'Pertanyaan',
                'description' => $data['description'] ?? null,
                'position' => 1,
            ]);

            foreach ($data['questions'] as $index => $question) {
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
