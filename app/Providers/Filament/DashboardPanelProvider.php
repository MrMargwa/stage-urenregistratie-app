<?php

namespace App\Providers\Filament;

// use Andreia\FilamentUiSwitcher\FilamentUiSwitcherPlugin;
use Andreia\FilamentUiSwitcher\FilamentUiSwitcherPlugin;
use App\Filament\Dashboard\Pages\Dashboard;
use App\Filament\Dashboard\Pages\Settings;
use App\Filament\Dashboard\Widgets\ProgressStats;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dashboard')
            ->default()
            ->path('dashboard')
            ->login()
            ->defaultThemeMode(ThemeMode::Dark)
            ->plugin(
                FilamentUiSwitcherPlugin::make()
                    ->withModeSwitcher()
            )
            ->themeSwitcher(false)
            ->viteTheme('resources/css/filament/dashboard/theme.css')
            ->discoverResources(in: app_path('Filament/Dashboard/Resources'), for: 'App\Filament\Dashboard\Resources')
            ->discoverPages(in: app_path('Filament/Dashboard/Pages'), for: 'App\Filament\Dashboard\Pages')
            ->pages([
                Dashboard::class,
                Settings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dashboard/Widgets'), for: 'App\Filament\Dashboard\Widgets')
            ->widgets([
                AccountWidget::class,
                ProgressStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => '<script src="' . asset('js/keepalive.js') . '?v=3"></script>',
            );
    }
}
