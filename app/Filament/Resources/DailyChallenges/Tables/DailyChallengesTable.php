<?php

namespace App\Filament\Resources\DailyChallenges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DailyChallengesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Player')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.current_streak')
                    ->label('Current Island (Streak)')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('score')
                    ->label('Score (Out of 10)')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 7 ? 'success' : ($state >= 4 ? 'warning' : 'danger'))
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('advanced_streak')
                    ->label('Island Jump Triggered')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Played At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('advanced_streak')
                    ->label('Show Only Island Jumps')
                    ->query(fn (Builder $query) => $query->where('advanced_streak', true)),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}