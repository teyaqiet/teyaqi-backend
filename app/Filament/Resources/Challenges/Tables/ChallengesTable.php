<?php

namespace App\Filament\Resources\Challenges\Tables;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Models\Category;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Collection;

class ChallengesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 🏆 Primary Info
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug), // Keeps the UI clean by hiding the slug under the title

                TextColumn::make('category.name')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ChallengeStatus $state): string => match ($state) {
                        ChallengeStatus::ACTIVE => 'success',
                        ChallengeStatus::DRAFT => 'warning',
                        ChallengeStatus::ARCHIVED => 'danger',
                    }),

                // 📊 Game Metrics
                TextColumn::make('question_count')
                    ->label('Questions')
                    ->numeric()
                    ->alignCenter()
                    ->summarize(Sum::make()->label('Total')),

                TextColumn::make('reward_xp')
                    ->label('XP')
                    ->numeric()
                    ->sortable()
                    ->color('primary'),

                // ⚙️ Flags
                IconColumn::make('is_daily')
                    ->label('Daily')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('start_at')
                    ->label('Starts')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ChallengeStatus::class),
                
                SelectFilter::make('category')
                    ->relationship('category', 'name'),

                TernaryFilter::make('is_daily')
                    ->label('Daily Challenge'),

                TernaryFilter::make('is_featured')
                    ->label('Featured Content'),
            ])
            ->actions([
                EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // 🚀 The "Go Live" Bulk Action
                    BulkAction::make('activate')
                        ->label('Publish Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => ChallengeStatus::ACTIVE])),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}