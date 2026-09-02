<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuestionOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Fresh Content: Questions that have never been shown to anyone
        $freshCount = Question::where('is_active', true)
            ->where('times_shown', 0)
            ->count();

        // 2. Translation Health: Checking if Amharic exists in the JSON column
        $amharicMissing = Question::where(function($query) {
                $query->whereNull('question_text->am')
                      ->orWhere('question_text->am', '');
            })->count();

        // 3. Global Accuracy: Calculating the average pass rate from your existing columns
        $totalShown = Question::sum('times_shown');
        $totalCorrect = Question::sum('times_correct');
        $averageSuccess = $totalShown > 0 ? round(($totalCorrect / $totalShown) * 100) : 0;

        return [
            Stat::make('Fresh Questions', $freshCount)
                ->description($freshCount < 20 ? 'Critical: Add more content' : 'Healthy Buffer')
                ->descriptionIcon($freshCount < 20 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($freshCount < 20 ? 'danger' : 'success'),

            Stat::make('Translation Debt', $amharicMissing)
                ->description('Needs Amharic text')
                ->color($amharicMissing > 0 ? 'warning' : 'success'),

            Stat::make('Global Accuracy', "{$averageSuccess}%")
                ->description('Overall Player Performance')
                ->color('info'),
        ];
    }
}