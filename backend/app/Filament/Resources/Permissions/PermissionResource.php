<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ListPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Permission';

    protected static ?string $modelLabel = 'permission';

    protected static ?string $pluralModelLabel = 'permission';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Kode permission')->searchable()->sortable(),
            TextColumn::make('roles.name')->label('Peran')->badge(),
            TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i'),
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('permissions.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListPermissions::route('/')];
    }
}
