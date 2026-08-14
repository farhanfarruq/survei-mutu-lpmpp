<?php

namespace App\Filament\Resources\InstrumentVersions\RelationManagers;

use App\Models\Indicator;
use App\Models\Scale;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Isi Formulir';

    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->hasRole('super_admin') ?? false) || ! $this->getOwnerRecord()->isEditable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('code')->default(fn (): string => 'BAGIAN-'.Str::upper(Str::random(8))),
            Hidden::make('position')->default(fn (): int => ((int) $this->getOwnerRecord()->sections()->max('position')) + 1),
            TextInput::make('title')->label('Nama bagian')->placeholder('Contoh: Pelayanan Akademik')->required()->maxLength(240),
            Textarea::make('description')->label('Petunjuk pengisian')->placeholder('Opsional')->columnSpanFull(),
            Repeater::make('questions')->label('Pertanyaan')->relationship()->schema([
                Hidden::make('code')->default(fn (): string => 'P-'.Str::upper(Str::random(8))),
                Hidden::make('indicator_id')->default(fn () => Indicator::query()->whereHas('category', fn ($query) => $query->where('instrument_version_id', $this->getOwnerRecord()->id))->value('id')),
                Hidden::make('scale_id')->default(fn () => Scale::query()->where('instrument_version_id', $this->getOwnerRecord()->id)->value('id'))->dehydrated(fn (Get $get): bool => $get('response_type') === 'scale'),
                Hidden::make('method')->default('internal'),
                Hidden::make('measurement_purpose')->default('Mengumpulkan jawaban responden.'),
                Textarea::make('item_text')->label('Tulis pertanyaan')->placeholder('Contoh: Seberapa puas Anda dengan layanan kami?')->required()->rows(2)->columnSpanFull(),
                Select::make('response_type')->label('Jenis jawaban')->options(['scale' => 'Skala kepuasan 1–5', 'single_choice' => 'Pilih satu jawaban', 'multiple_choice' => 'Pilih beberapa jawaban', 'short_text' => 'Jawaban singkat', 'long_text' => 'Jawaban panjang', 'number' => 'Angka'])->default('short_text')->required()->live(),
                Toggle::make('is_required')->label('Wajib diisi')->default(true),
                Textarea::make('help_text')->label('Petunjuk tambahan')->placeholder('Opsional')->columnSpanFull(),
                Repeater::make('options')->label('Pilihan jawaban')->relationship()->schema([
                    Hidden::make('code')->default(fn (): string => 'O-'.Str::upper(Str::random(8))),
                    TextInput::make('label')->label('Pilihan')->placeholder('Tulis pilihan jawaban')->required()->maxLength(300),
                ])->defaultItems(2)->orderColumn('position')->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                    $data['code'] ??= 'O-'.Str::upper(Str::random(8));

                    return $data;
                })->addActionLabel('Tambah pilihan')->visible(fn (Get $get): bool => in_array($get('response_type'), ['single_choice', 'multiple_choice'], true))->columnSpanFull(),
            ])->defaultItems(1)->orderColumn('position')->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                $data['code'] ??= 'P-'.Str::upper(Str::random(8));
                $data['indicator_id'] ??= Indicator::query()->whereIn('category_id', $this->getOwnerRecord()->categories()->pluck('id'))->value('id');
                $data['scale_id'] = $data['response_type'] === 'scale'
                    ? Scale::query()->where('instrument_version_id', $this->getOwnerRecord()->id)->value('id')
                    : null;
                $data['method'] ??= 'internal';
                $data['measurement_purpose'] ??= 'Mengumpulkan jawaban responden.';

                return $data;
            })->addActionLabel('Tambah pertanyaan')->columns(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('position')->label('Urutan')->sortable(),
            TextColumn::make('title')->label('Bagian formulir'),
            TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan'),
        ])->headerActions([CreateAction::make()->label('Tambah bagian')->visible(fn (): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $this->getOwnerRecord()->isEditable())])
            ->recordActions([
                EditAction::make()->label('Ubah')->visible(fn (): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $this->getOwnerRecord()->isEditable()),
                DeleteAction::make()->label('Hapus')->visible(fn (): bool => (auth()->user()?->hasRole('super_admin') ?? false) && $this->getOwnerRecord()->isEditable()),
            ])
            ->defaultSort('position');
    }
}
