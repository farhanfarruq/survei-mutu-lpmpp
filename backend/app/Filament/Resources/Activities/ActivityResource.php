<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?string $navigationLabel = 'Riwayat Aktivitas';

    protected static ?string $modelLabel = 'aktivitas';

    protected static ?string $pluralModelLabel = 'riwayat aktivitas';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->timezone('Asia/Jakarta')->sortable(),
                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        User::query()->select('name')->whereColumn('users.id', 'activity_log.causer_id')->limit(1),
                        $direction,
                    )),
                TextColumn::make('causer.roles.name')->label('Role')->badge(),
                TextColumn::make('event')->label('Aktivitas')->badge()->formatStateUsing(fn (?string $state): string => self::eventLabel($state)),
                TextColumn::make('subject')->label('Objek')->state(fn (Activity $record): string => self::subjectLabel($record))->wrap(),
                TextColumn::make('changes')->label('Ringkasan perubahan')->state(fn (Activity $record): string => self::changeSummary($record))->wrap()->placeholder('Tidak ada perubahan field'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('causer_id')->label('Pelaku')->options(fn () => User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', '!=', 'respondent'))->orderBy('name')->pluck('name', 'id'))->searchable(),
                SelectFilter::make('event')->label('Jenis aktivitas')->options(fn () => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event', 'event')),
                SelectFilter::make('role')->label('Role')->options([
                    'super_admin' => 'Super Admin',
                    'admin_lpmpp' => 'Admin LPMPP',
                    'leader' => 'Leader',
                ])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereHasMorph('causer', [User::class], fn (Builder $users) => $users->whereHas('roles', fn (Builder $roles) => $roles->where('name', $data['value'])))
                    : $query),
                Filter::make('created_at')->label('Rentang tanggal')->schema([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('until')->label('Sampai'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer.roles', 'subject'])
            ->whereHasMorph('causer', [User::class], fn (Builder $query) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', '!=', 'respondent')));
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('activity.read') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/')];
    }

    private static function eventLabel(?string $event): string
    {
        return match ($event) {
            'created', 'builder_created' => 'Membuat',
            'updated', 'builder_updated', 'schedule_updated' => 'Memperbarui',
            'deleted' => 'Menghapus',
            'login' => 'Login',
            'logout' => 'Logout',
            'submitted_for_review' => 'Mengajukan pemeriksaan',
            'returned' => 'Mengembalikan untuk diperbaiki',
            'approved' => 'Menyetujui',
            'published' => 'Mempublikasikan',
            'closed', 'closed_automatically' => 'Menutup survei',
            'archived' => 'Mengarsipkan',
            'duplicated', 'version_created', 'revision_created' => 'Membuat salinan/revisi',
            'access_assigned', 'access_updated' => 'Mengubah akses pengguna',
            default => str($event ?? 'aktivitas')->replace('_', ' ')->title()->toString(),
        };
    }

    private static function subjectLabel(Activity $activity): string
    {
        if (! $activity->subject) {
            return 'Sesi pengguna';
        }

        $type = class_basename($activity->subject_type);
        $name = $activity->subject->name ?? $activity->subject->code ?? $activity->subject_id;

        return "{$type}: {$name}";
    }

    private static function changeSummary(Activity $activity): string
    {
        $changes = $activity->attribute_changes?->get('attributes', []) ?? [];
        $safe = collect(array_keys(is_array($changes) ? $changes : []))
            ->reject(fn (string $key): bool => preg_match('/password|token|secret|answer|response|content/i', $key) === 1)
            ->map(fn (string $key): string => str($key)->replace('_', ' ')->toString())
            ->values();

        return $safe->isEmpty() ? '' : 'Mengubah: '.$safe->implode(', ');
    }
}
