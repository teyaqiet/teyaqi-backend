<?php

namespace App\Filament\Resources\Challenges\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChallengeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('status'),
                TextEntry::make('visibility')
                    ->badge(),
                TextEntry::make('thumbnail')
                    ->placeholder('-'),
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                TextEntry::make('topic_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('difficulty')
                    ->placeholder('-'),
                TextEntry::make('level_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('level_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('question_count')
                    ->numeric(),
                TextEntry::make('time_limit_seconds')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('passing_score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reward_xp')
                    ->numeric(),
                TextEntry::make('reward_coins')
                    ->numeric(),
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_daily')
                    ->boolean(),
                IconEntry::make('is_ranked')
                    ->boolean(),
                IconEntry::make('allow_retry')
                    ->boolean(),
                TextEntry::make('start_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('end_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('config')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
