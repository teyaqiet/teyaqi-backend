<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ContentHealthWidget;
use App\Filament\Widgets\OutlierQuestionsWidget;
use App\Filament\Widgets\CategoryDistributionChart;
use App\Filament\Widgets\DifficultySuccessChart;
use App\Filament\Widgets\ContentOverview;

use Filament\Pages\Page;

// Enums (Required for Filament v5 type-hinting)
use BackedEnum;
use UnitEnum;

class ContentDashboard extends Page
{
    // ✅ Matches your working reference exactly
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null $navigationGroup = 'Content Management';

    protected static ?string $title = 'Content Overview';

protected string $view = 'filament.pages.content-dashboard';
    /**
     * Pulls the health stats and outlier table into the dashboard
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ContentOverview::class, // Added here
            ContentHealthWidget::class,
            OutlierQuestionsWidget::class,
            CategoryDistributionChart::class, // Added here
            DifficultySuccessChart::class, // Added here
            

        ];
    }
}