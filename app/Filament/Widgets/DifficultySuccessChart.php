<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Widgets\ChartWidget;

class DifficultySuccessChart extends ChartWidget
{
    // 💡 FIXED: Removed "static" keyword
    protected ?string $heading = 'Avg. Success Rate by Difficulty';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Question::query()
            ->select('difficulty')
            ->selectRaw('AVG(CASE WHEN times_shown > 0 THEN (times_correct / times_shown) * 100 ELSE 0 END) as avg_sr')
            ->groupBy('difficulty')
            ->pluck('avg_sr', 'difficulty')
            ->toArray();

        $labels = ['easy', 'medium', 'hard'];
        $values = array_map(fn($label) => round($data[$label] ?? 0), $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Average SR %',
                    'data' => $values,
                    'backgroundColor' => ['#4ade80', '#fbbf24', '#f87171'],
                ],
            ],
            'labels' => ['Easy', 'Medium', 'Hard'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}