<?php

namespace App\Filament\Resources\InstrumentVersions\RelationManagers;

use App\Models\Indicator;
use App\Models\QuestionBankEntry;
use App\Models\Scale;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Bagian, Pertanyaan, dan Pilihan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(80),
            TextInput::make('title')->label('Judul')->required()->maxLength(240),
            TextInput::make('position')->label('Urutan')->numeric()->minValue(1)->required(),
            Textarea::make('description')->label('Petunjuk')->columnSpanFull(),
            Repeater::make('questions')->label('Pertanyaan')->relationship()->schema([
                Select::make('question_bank_entry_id')->label('Sumber bank pertanyaan')->options(QuestionBankEntry::query()->where('is_active', true)->orderBy('code')->pluck('item_text', 'id'))->searchable(),
                TextInput::make('code')->required()->maxLength(80),
                Textarea::make('item_text')->label('Item')->required()->rows(2)->columnSpanFull(),
                Select::make('indicator_id')->label('Indikator')->options(fn () => Indicator::query()->whereHas('category', fn (Builder $query) => $query->where('instrument_version_id', $this->getOwnerRecord()->id))->orderBy('code')->pluck('name', 'id'))->required()->searchable(),
                Select::make('scale_id')->label('Skala')->options(fn () => Scale::query()->where('instrument_version_id', $this->getOwnerRecord()->id)->orderBy('code')->pluck('name', 'id'))->searchable(),
                Select::make('response_type')->label('Jenis jawaban')->options(['scale' => 'Skala', 'single_choice' => 'Pilihan tunggal', 'multiple_choice' => 'Pilihan jamak', 'short_text' => 'Teks singkat', 'long_text' => 'Teks panjang', 'number' => 'Angka'])->required(),
                Select::make('method')->label('Metode')->options(['SERVPERF' => 'SERVPERF', 'SERVQUAL' => 'SERVQUAL', 'IPA' => 'IPA', 'CSI' => 'CSI', 'SKM' => 'SKM/IKM', 'NPS' => 'NPS', 'internal' => 'Internal'])->default('internal')->required(),
                TextInput::make('pair_code')->label('Kode pasangan')->maxLength(80),
                TextInput::make('position')->label('Urutan')->numeric()->minValue(1)->required(),
                Toggle::make('is_required')->label('Wajib')->default(true),
                Textarea::make('help_text')->label('Bantuan'),
                Textarea::make('measurement_purpose')->label('Tujuan pengukuran')->required(),
                Repeater::make('options')->label('Pilihan jawaban')->relationship()->schema([
                    TextInput::make('code')->required()->maxLength(80),
                    TextInput::make('label')->required()->maxLength(300),
                    TextInput::make('position')->label('Urutan')->numeric()->minValue(1)->required(),
                    TextInput::make('score_value')->label('Skor')->numeric(),
                    Toggle::make('is_exclusive')->label('Eksklusif'),
                ])->columns(3)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('position')->label('#')->sortable(), TextColumn::make('code'), TextColumn::make('title')->label('Judul'),
            TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan'),
        ])->headerActions([CreateAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())])
            ->recordActions([EditAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable()), DeleteAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())])
            ->defaultSort('position');
    }
}
