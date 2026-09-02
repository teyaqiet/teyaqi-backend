<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentHealthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('New Inventory', Question::where('times_shown', 0)->count())
                ->description('Questions players haven\'t seen')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),

            Stat::make('Translation Debt', Question::whereNull('question_text->am')
                ->orWhere('question_text->am', '')
                ->count())
                ->description('Missing Amharic text')
                ->descriptionIcon('heroicon-m-language')
                ->color('warning'),

            Stat::make('Media Gap', Question::whereNull('image_url')->count())
                ->description('Questions without 3D/Images')
                ->descriptionIcon('heroicon-m-photo')
                ->color('info'),
        ];
    }
}