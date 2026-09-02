<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User; // This tells the widget where the User model actually lives

class PlayerStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '30s';
    protected function getStats(): array
{
    return [
        Stat::make('Total Players', User::count())
            ->description('Total registered on Telegram')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('info'),
        Stat::make('Active Today', User::whereDate('last_played_date', now())->count())
            ->description('Players who answered a question today')
            ->descriptionIcon('heroicon-m-bolt')
            ->color('success'),
        Stat::make('Lives Drained', User::where('daily_lives', 0)->count())
            ->description('Players currently stuck at 0 hearts')
            ->descriptionIcon('heroicon-m-heart')
            ->color('danger'),
    ];
}
}
