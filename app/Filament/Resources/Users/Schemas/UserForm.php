<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('telegram_id')
                    ->tel()
                    ->default(null),
                TextInput::make('username')
                    ->default(null),
                TextInput::make('total_xp')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('current_sr')
                    ->required()
                    ->numeric()
                    ->default(50),
                TextInput::make('best_sr')
                    ->required()
                    ->numeric()
                    ->default(50),
                TextInput::make('total_answers_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('last_reward_at'),
                TextInput::make('current_streak')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('best_streak')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('last_played_date'),
                DateTimePicker::make('lives_updated_at'),
                TextInput::make('daily_lives')
                    ->required()
                    ->numeric()
                    ->default(5),
                TextInput::make('total_wins')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
