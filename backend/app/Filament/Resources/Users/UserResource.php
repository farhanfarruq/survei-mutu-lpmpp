<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Services\OrganizationalScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(160),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('password')
                ->label('Kata sandi')
                ->password()
                ->revealable(false)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(12),
            Toggle::make('is_active')->label('Akun aktif')->default(true),
            Select::make('roles')
                ->label('Peran')
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->can('organization.scope.all')
                        ? $query
                        : $query->where('name', '!=', 'super_admin'),
                )
                ->multiple()
                ->preload()
                ->searchable(),
            Select::make('organizationalUnits')
                ->label('Unit organisasi')
                ->relationship(
                    name: 'organizationalUnits',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->whereIn(
                        'organizational_units.id',
                        auth()->user() ? app(OrganizationalScope::class)->accessibleUnitIds(auth()->user()) : [],
                    ),
                )
                ->multiple()
                ->preload()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')->label('Peran')->badge(),
                TextColumn::make('organizationalUnits.code')->label('Unit')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('last_login_at')->label('Login terakhir')->dateTime('d M Y H:i')->placeholder('Belum pernah'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['roles', 'organizationalUnits']);
        $user = auth()->user();

        if (! $user || $user->can('organization.scope.all')) {
            return $query;
        }

        return $query->whereHas('organizationalUnits', fn (Builder $units) => $units->whereIn(
            'organizational_units.id',
            app(OrganizationalScope::class)->accessibleUnitIds($user),
        ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
