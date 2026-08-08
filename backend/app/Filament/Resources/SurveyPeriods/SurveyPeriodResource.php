<?php

namespace App\Filament\Resources\SurveyPeriods;

use App\Filament\Resources\SurveyPeriods\Pages\CreateSurveyPeriod;
use App\Filament\Resources\SurveyPeriods\Pages\EditSurveyPeriod;
use App\Filament\Resources\SurveyPeriods\Pages\ListSurveyPeriods;
use App\Models\SurveyPeriod;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SurveyPeriodResource extends Resource
{
    protected static ?string $model = SurveyPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Survei';

    protected static ?string $navigationLabel = 'Periode Survei';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Nama')->required()->maxLength(200),
            DatePicker::make('starts_on')->label('Mulai')->required(), DatePicker::make('ends_on')->label('Selesai')->required()->afterOrEqual('starts_on'),
            Select::make('timezone')->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->default('Asia/Jakarta')->required()->searchable(),
            Select::make('status')->options(['active' => 'Aktif', 'closed' => 'Selesai'])->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->label('Nama'), TextColumn::make('starts_on')->label('Mulai')->date(), TextColumn::make('ends_on')->label('Selesai')->date(), TextColumn::make('status')->badge()])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSurveyPeriods::route('/'), 'create' => CreateSurveyPeriod::route('/create'), 'edit' => EditSurveyPeriod::route('/{record}/edit')];
    }
}
