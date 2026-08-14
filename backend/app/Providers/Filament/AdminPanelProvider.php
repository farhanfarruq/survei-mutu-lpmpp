<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Controllers\RedirectToUnifiedLoginController;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(RedirectToUnifiedLoginController::class)
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationItems([
                NavigationItem::make('Buka Dashboard Pimpinan')
                    ->url(fn (): string => rtrim((string) config('app.frontend_url'), '/').'/app/analytics')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->group('Akses')
                    ->visible(fn (): bool => auth()->user()?->hasRole('leader') ?? false),
            ])
            ->navigation(function (): NavigationBuilder|bool {
                $user = auth()->user();

                if (! ($user?->hasAnyRole(['admin_lpmpp', 'super_admin']) ?? false)) {
                    return true;
                }

                $navigation = (new NavigationBuilder)
                    ->items(Dashboard::getNavigationItems())
                    ->group('Survei', [
                        ...CreateSurveyForm::getNavigationItems(),
                        ...InstrumentVersionResource::getNavigationItems(),
                        ...SurveyResource::getNavigationItems(),
                    ], collapsible: false);

                if ($user->hasRole('super_admin')) {
                    $navigation->group('Pengaturan Sistem', [
                        ...OrganizationalUnitResource::getNavigationItems(),
                        ...UserResource::getNavigationItems(),
                        ...RoleResource::getNavigationItems(),
                    ]);
                }

                return $navigation;
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
