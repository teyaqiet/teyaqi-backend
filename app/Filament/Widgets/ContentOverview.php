<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use App\Models\Category;
use App\Models\Level;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends BaseWidget
{
    protected static ?int $sort = 2; // Keep this at the top with PlayerStats

    protected function getStats(): array
    {
        return [
            Stat::make('Total Questions', Question::count())
                ->description('Active in the bot')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('primary'),

            Stat::make('Empty Categories', Category::doesntHave('questions')->count())
                ->description('Categories with 0 questions')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(Category::doesntHave('questions')->count() > 0 ? 'danger' : 'success'),

            Stat::make('Total Levels', Level::count())
                ->description('Game progression depth')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
        ];
    }
}