<?php

namespace App\Filament\Resources\InstrumentVersions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $title = 'Kategori dan Indikator';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(80),
            TextInput::make('name')->label('Nama')->required()->maxLength(200),
            TextInput::make('position')->label('Urutan')->numeric()->minValue(1)->required(),
            Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
            Repeater::make('indicators')->label('Indikator')->relationship()->schema([
                TextInput::make('code')->required()->maxLength(80),
                TextInput::make('name')->label('Nama')->required()->maxLength(200),
                TextInput::make('construct')->label('Konstruk')->required()->maxLength(160),
                TextInput::make('weight')->label('Bobot')->numeric()->minValue(0)->default(1)->required(),
                Textarea::make('interpretation')->label('Interpretasi'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('position')->label('#')->sortable(),
            TextColumn::make('code'),
            TextColumn::make('name')->label('Nama'),
            TextColumn::make('indicators_count')->counts('indicators')->label('Indikator'),
        ])->headerActions([CreateAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())])
            ->recordActions([EditAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable()), DeleteAction::make()->visible(fn () => $this->getOwnerRecord()->isEditable())])
            ->defaultSort('position');
    }
}
