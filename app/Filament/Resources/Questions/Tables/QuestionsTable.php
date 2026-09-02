<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Models\Category;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_text')
    ->label('Question')
    ->searchable()
    // Display English as the primary text
    ->formatStateUsing(fn ($record) => $record->getTranslation('question_text', 'en'))
    ->limit(50)
    ->url(fn ($record): string => route('filament.admin.resources.questions.view', $record))
    // Display Amharic as the secondary description text
    ->description(function ($record): string {
        $amharic = $record->getTranslation('question_text', 'am');
        return $amharic ?: 'No Amharic translation available';
    })
    ->wrap(), // Optional: allows the text to wrap if it's long


                TextColumn::make('category.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->description(fn ($record): string => "Score: {$record->difficulty_score}"),

                TextColumn::make('stats')
                    ->label('Stats')
                    ->getStateUsing(fn ($record) => "{$record->times_correct} / {$record->times_shown}"),

                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                // 📥 NEW: BULK EXPORT TO CSV
                    BulkAction::make('exportQuestions')
                        ->label('Export Selected')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return new StreamedResponse(function () use ($records) {
                                $handle = fopen('php://output', 'w');
                                
                                // Headers
                                fputcsv($handle, [
                                    'id', 'category', 'question_en', 'question_am', 
                                    'difficulty', 'difficulty_score', 'times_shown', 'times_correct'
                                ]);

                                foreach ($records as $record) {
                                    fputcsv($handle, [
                                        $record->id,
                                        $record->category?->name,
                                        $record->question_text['en'] ?? '',
                                        $record->question_text['am'] ?? '',
                                        $record->difficulty,
                                        $record->difficulty_score,
                                        $record->times_shown,
                                        $record->times_correct,
                                    ]);
                                }
                                fclose($handle);
                            }, 200, [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="teyaqi_export_'.now()->format('Y-m-d').'.csv"',
                            ]);
                        }),
                    // 💡 Bulk Difficulty Update
                    BulkAction::make('updateDifficulty')
                        ->label('Set Difficulty')
                        ->icon('heroicon-m-academic-cap')
                        ->form([
                            Select::make('difficulty')
                                ->options([
                                    'easy' => 'Easy',
                                    'medium' => 'Medium',
                                    'hard' => 'Hard',
                                ])->required(),
                            TextInput::make('difficulty_score')
                                ->numeric()
                                ->default(50)
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update([
                            'difficulty' => $data['difficulty'],
                            'difficulty_score' => $data['difficulty_score'],
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('changeCategory')
                        ->label('Change Category')
                        ->icon('heroicon-m-tag')
                        ->form([
                            Select::make('category_id')
                                ->options(Category::query()->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['category_id' => $data['category_id']]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('activate')
                        ->label('Set Active')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label('Set Inactive')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}