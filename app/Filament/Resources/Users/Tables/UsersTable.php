<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action; 
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Telegram')
                    ->prefix('@')
                    ->searchable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'player' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('total_xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_sr')
                    ->label('SR')
                    ->numeric()
                    ->sortable(),

                // ADDED DYNAMIC COLOR TO LIVES
                TextColumn::make('daily_lives')
                    ->label('Lives')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('current_streak')
                    ->label('Streak')
                    ->numeric()
                    ->sortable()
                    ->color('warning'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(), // Added this
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('reset_lives')
                        ->label('Reset Daily Lives')
                        ->icon('heroicon-m-bolt')
                        ->color('warning')
                        ->requiresConfirmation()
                        // FIX: THIS ALLOWS THE ACTION TO SEE THE CHECKBOXES
                        ->accessSelectedRecords() 
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update([
                                'daily_lives' => 3,
                                'lives_updated_at' => now(),
                            ]));

                            Notification::make()
                                ->title('Lives Reset')
                                ->body('Successfully reset lives for ' . $records->count() . ' players.')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}