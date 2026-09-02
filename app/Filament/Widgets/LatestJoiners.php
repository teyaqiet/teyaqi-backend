<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class LatestJoiners extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'half'; // Takes up 50% of the row

    public function table(Table $table): Table
    {
        return $table
            ->query(User::latest()->limit(5))
            ->columns([
                TextColumn::make('name')
                    ->label('New Player')
                    ->description(fn (User $record): string => '@' . ($record->username ?? 'no_username')),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since(),
            ])
            ->paginated(false);
    }
}