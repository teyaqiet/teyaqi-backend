<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('telegram_id')
                    ->placeholder('-'),
                TextEntry::make('username')
                    ->placeholder('-'),
                TextEntry::make('total_xp')
                    ->numeric(),
                TextEntry::make('current_sr')
                    ->numeric(),
                TextEntry::make('best_sr')
                    ->numeric(),
                TextEntry::make('total_answers_count')
                    ->numeric(),
                TextEntry::make('last_reward_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('current_streak')
                    ->numeric(),
                TextEntry::make('best_streak')
                    ->numeric(),
                TextEntry::make('last_played_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('lives_updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('daily_lives')
                    ->numeric(),
                TextEntry::make('total_wins')
                    ->numeric(),
            ]);
    }
}
