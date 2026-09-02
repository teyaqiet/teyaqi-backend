<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class LeaderboardWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '30s';    
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '🏆 Global Top 10 Leaderboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Get top 10 players by XP
                User::query()->orderBy('total_xp', 'desc')->limit(10)
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->getStateUsing(fn ($rowLoop) => $rowLoop->iteration)
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Player')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('username')
                    ->label('Telegram')
                    ->prefix('@')
                    ->color('primary')
                    ->extraAttributes(['class' => 'italic']),

                TextColumn::make('total_xp')
                    ->label('Total XP')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-sparkles')
                    ->sortable(),

                TextColumn::make('current_sr')
                    ->label('SR Rating')
                    ->numeric()
                    ->color('warning')
                    ->icon('heroicon-m-trophy'),

                TextColumn::make('current_streak')
                    ->label('Streak')
                    ->suffix(' days')
                    ->color('danger')
                    ->icon('heroicon-m-fire'),
            ])
            ->paginated(false); // Keeps it clean as a top-10 list
    }
}