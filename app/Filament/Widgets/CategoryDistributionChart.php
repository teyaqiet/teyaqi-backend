<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class CategoryDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Question Distribution by Category';
    protected int | string | array $columnSpan = 1;
    protected function getData(): array
    {
        $categories = Category::withCount('questions')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Questions',
                    'data' => $categories->pluck('questions_count')->toArray(),
                    'backgroundColor' => [
                        '#fbbf24', '#f87171', '#34d399', '#60a5fa', '#a78bfa', '#f472b6'
                    ],
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // Doughnut looks cleaner on dashboards
    }
}