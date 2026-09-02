<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PlayerActivityChart extends ChartWidget
{
    // REMOVED 'static'
    protected ?string $heading = 'Daily Active Players';
    
    // Sort usually stays static in most versions, but if this errors too, 
    // remove 'static' here as well.
    protected static ?int $sort = 2; 

    protected function getData(): array
    {
        // Counts users who had a 'last_played_date' in the last 7 days
        $data = Trend::model(User::class)
            ->between(
                start: now()->startOfWeek(),
                end: now()->endOfWeek(),
            )
            ->perDay()
            ->dateColumn('last_played_date')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Active Players',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#f59e0b', // Teyaqi Orange
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}