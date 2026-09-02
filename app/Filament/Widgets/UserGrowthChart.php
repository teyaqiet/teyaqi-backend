<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserGrowthChart extends ChartWidget
{
    // REMOVE 'static' here
    protected ?string $heading = 'Monthly New Signups';
    protected int | string | array $columnSpan = 'half';
    
    // Some versions use static for sort, others don't. 
    // If it still errors, remove static from here too.
    protected static ?int $sort = 3; 

    protected function getData(): array
    {
        $users = User::select(DB::raw('COUNT(id) as count'), DB::raw("DATE_FORMAT(created_at, '%M') as month"))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('created_at')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $users->pluck('count'),
                    'backgroundColor' => '#3b82f6', // Teyaqi Blue/Primary
                ],
            ],
            'labels' => $users->pluck('month'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}