<?php

namespace App\Filament\Resources\Levels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('level_number')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('min_xp')
                    ->required()
                    ->numeric(),
                TextInput::make('hex_color')
                    ->required()
                    ->default('#3b82f6'),
            ]);
    }
}
