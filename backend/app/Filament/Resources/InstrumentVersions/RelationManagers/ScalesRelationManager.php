<?php

namespace App\Filament\Resources\InstrumentVersions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScalesRelationManager extends RelationManager
{
    protected static string $relationship = 'scales';

    protected static ?string $title = 'Skala dan Pilihan Skala';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(80),
            TextInput::make('name')->label('Nama')->required()->maxLength(200),
            Select::make('scale_type')->label('Jenis')->options(['likert' => 'Likert', 'numeric' => 'Numerik', 'single_choice' => 'Pilihan tunggal'])->required(),
            TextInput::make('min_value')->label('Minimum')->numeric(),
            TextInput::make('max_value')->label('Maksimum')->numeric()->gt('min_value'),
            Toggle::make('na_allowed')->label('Izinkan N/A'),
            Select::make('missing_policy')->label('Missing data')->options(['exclude_item' => 'Keluarkan item', 'exclude_response' => 'Keluarkan respons', 'report_only' => 'Laporkan saja'])->default('exclude_item')->required(),
            Repeater::make('points')->label('Pilihan skala')->relationship()->schema([
                TextInput::make('code')->required()->maxLength(40),
                TextInput::make('label')->required()->maxLength(200),
                TextInput::make('numeric_value')->label('Nilai')->numeric(),
                TextInput::make('position')->label('Urutan')->numeric()->minValue(1)->required(),
                Toggle::make('is_na')->label('N/A'),
                Toggle::make('is_neutral')->label('Netral'),
            ])->columns(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code'), TextColumn::make('name')->label('Nama'), TextColumn::make('scale_type')->label('Jenis')->badge(),
            TextColumn::make('points_count')->counts('points')->label('Pilihan'),
        ])->headerActions([CreateAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())])
            ->recordActions([EditAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable()), DeleteAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())]);
    }
}
