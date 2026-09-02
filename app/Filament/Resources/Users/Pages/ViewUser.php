<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group; // Added Group for hard breaks
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->label('Modify Player'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ## ROW 1: IDENTITY & CONTACT (Wrapped in Group to force new line)
                Group::make([
                    Grid::make(2)->schema([
                        Section::make('Player Hero')
                        ->columns(2)
                            ->schema([
                                Placeholder::make('name')
                                    ->label('Display Name')
                                    ->content(fn ($record) => new HtmlString('
                                        <div>
                                            <div class="text-2xl font-black text-white">' . ($record->name ?? 'Guest') . '</div>
                                            <div class="text-primary-500 font-mono text-sm">@' . ($record->username ?? 'no_username') . '</div>
                                        </div>
                                    ')),
                            ])->extraAttributes(['class' => 'border-l-4 border-l-primary-600 bg-primary-500/5']),

                        Section::make('Contact Details')
                        ->columns(2)
                            ->schema([
                                Placeholder::make('email_card')->label('Email')->content(fn ($record) => $record->email),
                                Placeholder::make('telegram_id')->label('Telegram ID')->content(fn ($record) => $record->telegram_id ?? 'N/A'),
                            ]),
                    ]),
                ])->columnSpanFull(),

                ## ROW 2: PERFORMANCE & STREAKS (Wrapped in Group to force new line)
                Group::make([
                    Grid::make(2)->schema([
                        Section::make('Gameplay Performance')
                            ->columns(3)
                            ->schema([
                                Placeholder::make('total_xp')->label('Experience')->content(fn ($record) => new HtmlString('<span class="text-xl font-bold text-white">' . number_format($record->total_xp ?? 0) . ' XP</span>')),
                                Placeholder::make('total_wins')->label('Total Wins')->content(fn ($record) => new HtmlString('<span class="text-success-500 font-bold text-xl">' . number_format($record->total_wins ?? 0) . '</span>')),
                                Placeholder::make('daily_lives')->label('Lives')->content(fn ($record) => new HtmlString('<span class="text-danger-500 font-bold text-xl">❤️ ' . ($record->daily_lives ?? 0) . '</span>')),
                            ]),

                        Section::make('Streak & Rank Data')
                            ->columns(3)
                            ->schema([
                                Placeholder::make('current_streak')->label('Current Streak')->content(fn ($record) => new HtmlString('<span class="text-orange-500 font-black text-xl">🔥 ' . ($record->current_streak ?? 0) . ' Days</span>')),
                                Placeholder::make('current_sr')->label('Current SR')->content(fn ($record) => new HtmlString('<span class="font-bold text-white text-xl">' . ($record->current_sr ?? 0) . '</span>')),
                                Placeholder::make('best_sr')->label('Peak SR')->content(fn ($record) => new HtmlString('<span class="font-bold text-white text-xl">' . ($record->best_sr ?? 0) . '</span>')),
                            ]),
                    ]),
                ])->columnSpanFull(),

                ## ROW 3: TIMELINE (Wrapped in Group to force new line)
                Group::make([
                    Section::make('Activity Timeline')
                        ->columns(4)
                        ->schema([
                            Placeholder::make('last_played')->label('Last Match')->content(fn ($record) => $record->last_played_date ? $record->last_played_date->diffForHumans() : 'Never'),
                            Placeholder::make('last_reward')->label('Last Reward')->content(fn ($record) => $record->last_reward_at ? $record->last_reward_at->diffForHumans() : 'None'),
                            Placeholder::make('lives_updated')->label('Lives Refilled')->content(fn ($record) => $record->lives_updated_at ? $record->lives_updated_at->diffForHumans() : 'N/A'),
                            Placeholder::make('created_at')->label('Join Date')->content(fn ($record) => $record->created_at ? $record->created_at->format('M d, Y') : '—'),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}