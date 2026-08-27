<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CreateSurveyForm;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\InstrumentVersions\InstrumentVersionResource;
use App\Filament\Resources\OrganizationalUnits\OrganizationalUnitResource;
use App\Filament\Resources\QuestionBankEntries\QuestionBankEntryResource;
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
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
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
            ->brandLogo(asset('itda-logo.webp'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('itda-logo.webp'))
            ->colors([
                'primary' => Color::Sky,
            ])
            ->databaseNotifications(fn (): bool => auth()->user()?->can('notification.read') ?? false)
            ->databaseNotificationsPolling('30s')
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .fi-topbar-database-notifications-btn .fi-badge,
                    .fi-sidebar-database-notifications-btn .fi-badge {
                        min-width: 1.25rem;
                        border-radius: 9999px;
                        background: #dc2626 !important;
                        color: #fff !important;
                    }
                </style>
                HTML))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationItems([
                $this->analyticsNavigationItem(),
            ])
            ->navigation(function (): NavigationBuilder|bool {
                $user = auth()->user();

                if (! ($user?->hasAnyRole(['admin_lpmpp', 'super_admin']) ?? false)) {
                    return true;
                }

                $navigation = (new NavigationBuilder)
                    ->items([
                        ...Dashboard::getNavigationItems(),
                        $this->analyticsNavigationItem(),
                    ])
                    ->group('Survei', [
                        ...CreateSurveyForm::getNavigationItems(),
                        ...QuestionBankEntryResource::getNavigationItems(),
                        ...InstrumentVersionResource::getNavigationItems(),
                        ...SurveyResource::getNavigationItems(),
                    ], collapsible: false);

                $navigation->group('Pengawasan', [
                    ...ActivityResource::getNavigationItems(),
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

    private function analyticsNavigationItem(): NavigationItem
    {
        return NavigationItem::make('Buka Dashboard Hasil Survei')
            ->url(fn (): string => rtrim((string) config('app.frontend_url'), '/').'/app/analytics')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->group('Akses')
            ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin_lpmpp', 'super_admin', 'leader']) ?? false);
    }
}
