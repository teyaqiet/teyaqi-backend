<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActiveUsersChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '30s';
    protected ?string $heading = 'Daily Active Players';

    protected function getData(): array
    {
        // Get activity for the last 7 days
        $data = User::select(
                DB::raw('DATE(last_played_date) as date'), 
                DB::raw('count(*) as count')
            )
            ->whereNotNull('last_played_date')
            ->where('last_played_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Players',
                    'data' => $data->pluck('count')->toArray(),
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#fbbf24', 
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('D'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}