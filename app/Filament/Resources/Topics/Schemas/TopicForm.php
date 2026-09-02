<?php

namespace App\Filament\Resources\Topics\Schemas;

use App\Models\Category;
use App\Models\Topic;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->description('Define your topic metadata and bind it to a core parent category.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // 📂 Target Parent Category Selector
                                Select::make('category_id')
                                    ->label('Parent Category')
                                    ->options(fn () => Category::pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                // 📝 Topic Name Input with Auto-Slugging
                                TextInput::make('name')
                                    ->label('Topic Name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, callable $set) => 
                                        $set('slug', Str::slug($state))
                                    ),
                            ]),

                        Grid::make(1)
                            ->schema([
                                // 🔗 URL Permastruct Slug Field
                                TextInput::make('slug')
                                    ->label('URL Slug Identifier')
                                    ->required()
                                    ->unique(Topic::class, 'slug', ignoreRecord: true),
                            ]),
                    ]),
            ]);
    }
}