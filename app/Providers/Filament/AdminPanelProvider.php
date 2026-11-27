<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\BlockDemoMutations;
use App\Http\Middleware\VerifyLicense;
use App\Providers\DemoModeServiceProvider;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
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
            ->login(Login::class)
            ->profile(EditProfile::class, isSimple: false)
            ->brandName('NetSpeed ISP Billing')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo.png'))
            ->colors([
                'primary' => Color::hex('#00e07e'),
                'gray' => Color::Zinc,
                'info' => Color::hex('#4deda4'),
                'success' => Color::hex('#00e07e'),
                'warning' => Color::hex('#f2c56b'),
                'danger' => Color::hex('#f27b7b'),
            ])
            ->font('Plus Jakarta Sans', provider: LocalFontProvider::class)
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16.5rem')
            ->collapsedSidebarWidth('4.5rem')
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                NavigationGroup::make('Pelanggan')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('Billing')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Jaringan')
                    ->icon('heroicon-o-signal'),
                NavigationGroup::make('Teknisi')
                    ->icon('heroicon-o-wrench-screwdriver'),
                NavigationGroup::make('Notifikasi')
                    ->icon('heroicon-o-chat-bubble-left-right'),
                NavigationGroup::make('Pengaturan')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->spa()
            ->unsavedChangesAlerts(! DemoModeServiceProvider::isDemo())
            ->resourceEditPageRedirect('index')
            ->resourceCreatePageRedirect('index')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.components.custom-styles'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.demo-banner'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn () => view('filament.components.login-mascot'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_END,
                fn () => view('filament.components.login-footer'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
                BlockDemoMutations::class,
                VerifyLicense::class,
            ])
            ->authMiddleware([
                AuthenticateAdminPanel::class,
            ]);
    }
}
