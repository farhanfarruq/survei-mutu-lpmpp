<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    private const PERMISSION_SUBJECTS = [
        'admin.panel' => ['Akses sistem', 'panel admin'],
        'system.status' => ['Akses sistem', 'status sistem'],
        'system.horizon' => ['Akses sistem', 'antrean proses'],
        'organization.scope' => ['Organisasi', 'seluruh unit organisasi'],
        'organizational-units' => ['Organisasi', 'unit organisasi'],
        'users' => ['Pengguna & peran', 'pengguna'],
        'roles' => ['Pengguna & peran', 'peran'],
        'permissions' => ['Pengguna & peran', 'daftar izin akses'],
        'template' => ['Instrumen survei', 'template instrumen'],
        'validation' => ['Instrumen survei', 'validasi instrumen'],
        'campaign' => ['Survei & responden', 'survei'],
        'population' => ['Survei & responden', 'populasi responden'],
        'analysis' => ['Analisis & laporan', 'analisis'],
        'report' => ['Analisis & laporan', 'laporan'],
        'ai' => ['AI & notifikasi', 'AI'],
        'notification' => ['AI & notifikasi', 'notifikasi'],
        'finding' => ['Temuan & tindak lanjut', 'temuan'],
        'action' => ['Temuan & tindak lanjut', 'tindak lanjut'],
        'follow-up.dashboard' => ['Temuan & tindak lanjut', 'dasbor tindak lanjut'],
    ];

    private const PERMISSION_ACTIONS = [
        'access' => 'Akses',
        'all' => 'Akses',
        'view' => 'Lihat',
        'read' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'review' => 'Tinjau',
        'approve' => 'Setujui',
        'publish' => 'Terbitkan',
        'manage' => 'Kelola',
        'execute' => 'Jalankan',
        'release' => 'Rilis',
        'export' => 'Ekspor',
        'config' => 'Atur',
        'verify' => 'Verifikasi',
    ];

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Peran';

    protected static ?string $modelLabel = 'peran';

    protected static ?string $pluralModelLabel = 'peran';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Kode peran')->required()->maxLength(100)->unique(ignoreRecord: true),
            Select::make('permissions')
                ->label('Hak akses')
                ->relationship('permissions', 'name')
                ->options(fn (): array => static::permissionOptions())
                ->multiple()
                ->preload()
                ->searchable()
                ->helperText('Hak akses dikelompokkan berdasarkan bagian agar lebih mudah dipilih.'),
        ]);
    }

    public static function permissionOptions(): array
    {
        $options = [];

        foreach (self::PERMISSION_SUBJECTS as [$group]) {
            $options[$group] ??= [];
        }

        foreach (Permission::query()->orderBy('name')->get() as $permission) {
            [$group, $label] = static::permissionPresentation($permission->name);
            $options[$group][$permission->getKey()] = $label;
        }

        return array_filter($options);
    }

    public static function permissionLabel(string $permission): string
    {
        return static::permissionPresentation($permission)[1];
    }

    private static function permissionPresentation(string $permission): array
    {
        $parts = explode('.', $permission);
        $action = array_pop($parts);
        $subject = implode('.', $parts);
        [$group, $subjectLabel] = self::PERMISSION_SUBJECTS[$subject]
            ?? ['Lainnya', Str::lower(Str::headline($subject))];
        $actionLabel = self::PERMISSION_ACTIONS[$action] ?? Str::headline($action);

        return [$group, "{$actionLabel} {$subjectLabel}"];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Peran')->searchable()->sortable(),
            TextColumn::make('permissions.name')
                ->label('Hak akses')
                ->formatStateUsing(fn (string $state): string => static::permissionLabel($state))
                ->badge()
                ->limitList(5),
            TextColumn::make('users_count')->label('Pengguna')->counts('users'),
        ])->recordActions([
            EditAction::make()->visible(fn (Role $record): bool => static::canEdit($record)),
        ]);
    }

    public static function canViewAny(): bool
    {
        return static::isSuperAdmin() && auth()->user()?->can('roles.view') === true;
    }

    public static function canCreate(): bool
    {
        return static::isSuperAdmin() && auth()->user()?->can('roles.create') === true;
    }

    public static function canEdit(Model $record): bool
    {
        return static::isSuperAdmin()
            && auth()->user()?->can('roles.update') === true
            && $record->getAttribute('name') !== 'super_admin';
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    private static function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
