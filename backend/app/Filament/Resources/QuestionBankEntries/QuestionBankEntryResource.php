<?php

namespace App\Filament\Resources\QuestionBankEntries;

use App\Filament\Resources\QuestionBankEntries\Pages\CreateQuestionBankEntry;
use App\Filament\Resources\QuestionBankEntries\Pages\EditQuestionBankEntry;
use App\Filament\Resources\QuestionBankEntries\Pages\ListQuestionBankEntries;
use App\Models\OrganizationalUnit;
use App\Models\QuestionBankEntry;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            Select::make('owner_unit_id')->label('Unit pemilik')->options(OrganizationalUnit::query()->whereIn('id', $ids)->orderBy('code')->pluck('name', 'id'))->required()->searchable(),
            TextInput::make('code')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('family_code')->label('Keluarga')->required()->maxLength(80),
            Select::make('method')->label('Metode')->options(['SERVPERF' => 'SERVPERF', 'SERVQUAL' => 'SERVQUAL', 'IPA' => 'IPA', 'CSI' => 'CSI', 'SKM' => 'SKM/IKM', 'NPS' => 'NPS', 'internal' => 'Internal'])->required(),
            TextInput::make('category_label')->label('Kategori')->required()->maxLength(200),
            TextInput::make('indicator_label')->label('Indikator')->required()->maxLength(200),
            Select::make('response_type')->label('Jenis jawaban')->options(['scale' => 'Skala', 'single_choice' => 'Pilihan tunggal', 'multiple_choice' => 'Pilihan jamak', 'short_text' => 'Teks singkat', 'long_text' => 'Teks panjang', 'number' => 'Angka'])->required(),
            Textarea::make('item_text')->label('Item')->required()->rows(3)->columnSpanFull(),
            Textarea::make('help_text')->label('Bantuan')->columnSpanFull(),
            Textarea::make('measurement_purpose')->label('Tujuan pengukuran')->required()->columnSpanFull(),
            Repeater::make('default_options')->label('Pilihan bawaan')->schema([
                TextInput::make('code')->required()->maxLength(80),
                TextInput::make('label')->required()->maxLength(300),
                TextInput::make('score_value')->label('Skor')->numeric(),
                Toggle::make('is_exclusive')->label('Eksklusif'),
            ])->columns(2)->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable(), TextColumn::make('item_text')->label('Item')->limit(70)->searchable(),
            TextColumn::make('method')->badge(), TextColumn::make('ownerUnit.code')->label('Unit'), IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([EditAction::make()]);
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
