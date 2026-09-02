<?php

namespace App\Traits;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SendsTelegramNotifications
{
    public function dispatchTelegram($user, $message, $token = null, $btnText = null, $btnUrl = null, $type = 'manual'): bool
    {
        $token = $token ?? config('services.telegram.bot_token');

        if (!$token || !$user->telegram_id) {
            return false;
        }

        $text = str_replace(
            ['{name}', '{xp}', '{life}', '{streak}'],
            [$user->name, $user->total_xp ?? 0, $user->daily_lives ?? 0, $user->current_streak ?? 0],
            $message
        );

        $payload = [
            'chat_id' => $user->telegram_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($btnText && $btnUrl) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [[['text' => $btnText, 'url' => $btnUrl]]]
            ]);
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            $isSuccessful = $response->successful();

            // SAVE THE LOG
            NotificationLog::create([
                'user_id' => $user->id,
                'message' => $text,
                'type' => $type,
                'is_sent' => $isSuccessful,
            ]);

            return $isSuccessful;
        } catch (\Exception $e) {
            Log::error("Telegram Notification Failed: " . $e->getMessage());
            return false;
        }
    }
}