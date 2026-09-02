<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;

class OutlierQuestionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Question::query()
                    ->where('times_shown', '>', 5)
                    ->where(function (Builder $query) {
                        $query->where(function ($q) {
                            $q->where('difficulty', 'easy')->whereRaw('(times_correct / times_shown) < 0.70');
                        })->orWhere(function ($q) {
                            $q->where('difficulty', 'hard')->whereRaw('(times_correct / times_shown) > 0.60');
                        })->orWhere(function ($q) {
                            $q->where('difficulty', 'medium')->whereRaw('(times_correct / times_shown) < 0.35 OR (times_correct / times_shown) > 0.75');
                        });
                    })
            )
            ->recordUrl(fn (Question $record): string => route('filament.admin.resources.questions.edit', ['record' => $record]))
            
            ->columns([
                // QUESTION TEXT
                TextColumn::make('question_text.en')
                    ->label('Question')
                    ->limit(40)
                    ->wrap(),

                // DIFFICULTY SCORE (The raw 0-100 value)
                TextColumn::make('difficulty_score')
                    ->label('Score')
                    ->alignCenter()
                    ->description(fn (Question $record): string => strtoupper($record->difficulty)),

                // SR % WITH VISUAL CONTEXT
                TextColumn::make('success_rate')
                    ->label('SR %')
                    ->badge()
                    ->getStateUsing(function (Question $record): string {
                        if ($record->times_shown === 0) return '0%';
                        return round(($record->times_correct / $record->times_shown) * 100) . '%';
                    })
                    ->color(fn (Question $record): string => match (true) {
                        ($record->times_correct / $record->times_shown) < 0.20 => 'danger',
                        ($record->times_correct / $record->times_shown) < 0.40 => 'warning',
                        default => 'success',
                    })
                    ->description(fn (Question $record): string => "{$record->times_correct} / {$record->times_shown} hits"),

                // THE ULTIMATE TREND/ACTION COLUMN
                TextColumn::make('trend')
                    ->label('Action Needed')
                    ->badge()
                    ->getStateUsing(function (Question $record): string {
                        $rate = $record->times_correct / $record->times_shown;
                        
                        return match (true) {
                            $rate < 0.15 => '🔥 Critical: Too Hard',
                            $rate > 0.85 => '💎 Critical: Too Easy',
                            $record->difficulty === 'easy' && $rate < 0.60 => 'Downgrade to Medium',
                            $record->difficulty === 'hard' && $rate > 0.70 => 'Upgrade to Medium',
                            $record->difficulty === 'medium' && $rate < 0.30 => 'Move to Hard',
                            $record->difficulty === 'medium' && $rate > 0.80 => 'Move to Easy',
                            default => 'Check Phrasing',
                        };
                    })
                    ->color(fn (string $state): string => str_contains($state, 'Critical') ? 'danger' : 'warning'),

                // LAST UPDATED
                TextColumn::make('updated_at')
                    ->label('Last Edit')
                    ->dateTime('M d')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([5, 10])
            ->striped();
    }
}