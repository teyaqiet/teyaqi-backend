<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ImageEntry; // 💡 If your Filament version supports it
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewQuestion extends ViewRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->icon('heroicon-m-pencil-square')->color('warning'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ## 1. PERFORMANCE STATS
                Section::make('Performance')
                    ->columns(4)
                    ->schema([
                        Placeholder::make('rate')->label('Pass Rate')
                            ->content(fn ($record) => ($record->times_shown > 0 ? number_format(($record->times_correct / $record->times_shown) * 100, 1) : 0) . '%'),
                        Placeholder::make('shown')->label('Impressions')->content(fn ($record) => $record->times_shown),
                        Placeholder::make('correct')->label('Correct Hits')->content(fn ($record) => $record->times_correct),
                        Placeholder::make('status')->label('Status')
                            ->content(fn ($record) => $record->is_active ? '✅ LIVE' : '🟠 DRAFT'),
                    ]),

                ## 4. METADATA
                Section::make('Metadata')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('cat')->label('Category')->content(fn ($record) => $record->category?->name ?? 'N/A'),
                        Placeholder::make('diff')->label('Difficulty')->content(fn ($record) => strtoupper($record->difficulty)),
                        Placeholder::make('score')->label('score')->content(fn ($record) => $record->difficulty_score . ' pts'),
                    ]),

                ## 2. THE QUESTION
                Section::make('Question Content')
                    ->schema([
                        # Image Preview (Handles null automatically)
                        Placeholder::make('image_url')
                            ->label('Attached Image')
                            ->content(fn($record) => $record->image_url 
                                ? new HtmlString("<img src='/storage/{$record->image_url}' class='rounded-lg border border-gray-700 w-48'>") 
                                : 'No image'),

                        Grid::make(2)->schema([
                            Placeholder::make('en_text')->label('English')
                                ->content(fn ($record) => $record->question_text['en'] ?? '—'),
                            Placeholder::make('am_text')->label('Amharic')
                                ->content(fn ($record) => $record->question_text['am'] ?? '—'),
                        ]),
                    ]),

                ## 3. THE CHOICES (FIXED LOGIC)
                Section::make('Answer Options')
                    ->schema(function ($record) {
                        $choices = ['a', 'b', 'c', 'd'];
                        $components = [];

                        foreach ($choices as $key) {
                            $columnName = "option_{$key}";
                            $isCorrect = strtolower($record->correct_answer) === $key;
                            
                            $components[] = Section::make()
                                ->heading("Option " . strtoupper($key))
                                ->compact()
                                ->extraAttributes([
                                    'class' => $isCorrect 
                                        ? 'border-l-4 border-l-success-500 bg-success-500/5' 
                                        : 'border-l-4 border-l-gray-700'
                                ])
                                ->schema([
                                    Grid::make(2)->schema([
                                        Placeholder::make("{$key}_en")
                                            ->label('English')
                                            ->content($record->$columnName['en'] ?? '—'),
                                        Placeholder::make("{$key}_am")
                                            ->label('Amharic')
                                            ->content($record->$columnName['am'] ?? '—'),
                                    ]),
                                    
                                    # Fixed: Uses a Placeholder that only renders if it's the correct choice
                                    Placeholder::make("{$key}_correct")
                                        ->label('')
                                        ->hidden(!$isCorrect) // 💡 Correct way to handle conditional fields
                                        ->content(new HtmlString('<span class="text-success-500 font-bold text-xs uppercase tracking-widest">✅ Correct Answer</span>')),
                                ]);
                        }
                        return $components;
                    }),

                
            ]);
    }
}