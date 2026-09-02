<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Tabs; // Import Tabs
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;



class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

            // TRANSLATION TABS
                Tabs::make('Content Translations')
                    ->columnSpanFull()
                    ->tabs([
                        // ENGLISH TAB
                        Tabs\Tab::make('English')
                            ->icon('heroicon-m-language')
                            ->schema([
                                Textarea::make('question_text.en')
                                    ->label('Question (English)')
                                    ->required()
                                    ->columnSpanFull(),
                                
                                TextInput::make('option_a.en')->label('Option A')->required(),
                                TextInput::make('option_b.en')->label('Option B')->required(),
                                TextInput::make('option_c.en')->label('Option C')->required(),
                                TextInput::make('option_d.en')->label('Option D')->required(),

                                Textarea::make('explanation.en')
                                    ->label('Explanation (English)')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // AMHARIC TAB
                        Tabs\Tab::make('Amharic')
                            ->icon('heroicon-m-language')
                            ->schema([
                                Textarea::make('question_text.am')
                                    ->label('ጥያቄ (Amharic)')
                                    ->required()
                                    ->columnSpanFull(),
                                
                                TextInput::make('option_a.am')->label('ምርጫ ሀ')->required(),
                                TextInput::make('option_b.am')->label('ምርጫ ለ')->required(),
                                TextInput::make('option_c.am')->label('ምርጫ ሐ')->required(),
                                TextInput::make('option_d.am')->label('ምርጫ መ')->required(),

                                Textarea::make('explanation.am')
                                    ->label('ማብራሪያ (Amharic)')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ]),

                    
                // Metadata Section (Category, Difficulty, Image)
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required(),
                        
                        Select::make('difficulty')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->required()
                            ->default('easy'),

                        FileUpload::make('image_url')
                            ->label('Question Image')
                            ->image()
                            ->disk('public')
                            ->directory('questions')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                

                // Logic & Visibility
                Section::make('Settings')
                    ->columns(2)
                    ->schema([
                        Select::make('correct_answer')
                            ->options([
                                'a' => 'Option A',
                                'b' => 'Option B',
                                'c' => 'Option C',
                                'd' => 'Option D',
                            ])
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('Visible in Bot?')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}