<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ActiveUsersChart;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Widgets\PlayerStats;
use App\Filament\Widgets\LatestJoiners;
use App\Filament\Widgets\LeaderboardWidget;
use App\Filament\Widgets\UserGrowthChart;
use App\Filament\Widgets\PlayerActivityChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\SpatieLaravelTranslatablePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->path('filament')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // We keep this to automatically find any new widgets you create
            ->widgets([
                PlayerStats::class,      // Sort 1
                ActiveUsersChart::class, // Sort 2
                LatestJoiners::class,    // Sort 3
                LeaderboardWidget::class,// Sort 4 (Full width at bottom)
                UserGrowthChart::class,// Sort 4 (Full width at bottom)
                PlayerActivityChart::class,// Sort 4 (Full width at bottom)
            ])
        
        
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
            
            
    }
}