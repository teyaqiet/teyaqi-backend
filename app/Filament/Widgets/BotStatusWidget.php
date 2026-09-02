<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class BotStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $botToken = config('services.telegram.bot_token');
        $isOnline = false;
        $botName = 'Unknown';

        try {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
            if ($response->successful()) {
                $isOnline = true;
                $botName = $response->json('result.first_name');
            }
        } catch (\Exception $e) {
            $isOnline = false;
        }

        return [
            Stat::make('Telegram Bot Status', $isOnline ? 'Online' : 'Offline')
                ->description($isOnline ? "Connected as {$botName}" : 'Check your .env token')
                ->descriptionIcon($isOnline ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($isOnline ? 'success' : 'danger'),
        ];
    }
}