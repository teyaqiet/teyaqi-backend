<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected TelegramAuthService $telegramAuth;

    public function __construct(TelegramAuthService $telegramAuth)
    {
        $this->telegramAuth = $telegramAuth;
    }

    public function telegramLogin(Request $request)
    {
        $initData = $request->input('init_data');

        $botToken = config('services.telegram.bot_token')
            ?? env('TELEGRAM_BOT_TOKEN');

        if (!$initData) {
            return response()->json([
                'error' => 'Missing Telegram auth data.',
            ], 400);
        }

        try {
            // =====================================================
            // 1. TELEGRAM SIGNATURE VALIDATION
            // =====================================================

            if (!$botToken) {
                throw new \Exception(
                    'TELEGRAM_BOT_TOKEN is missing or unconfigured.'
                );
            }

            if (!$this->telegramAuth->validate($initData, $botToken)) {
                Log::warning('Telegram authentication rejected.', [
                    'reason' => 'Invalid Telegram signature',
                ]);

                return response()->json([
                    'error' => 'Invalid Telegram authentication data.',
                ], 403);
            }

            // =====================================================
            // 2. PARSE TELEGRAM USER
            // =====================================================

            parse_str($initData, $data);

            if (!isset($data['user'])) {
                return response()->json([
                    'error' => 'Telegram user data is missing.',
                ], 400);
            }

            $userRaw = json_decode($data['user'], true);

            if (
                !is_array($userRaw) ||
                !isset($userRaw['id'])
            ) {
                return response()->json([
                    'error' => 'Invalid Telegram user data.',
                ], 400);
            }

            $telegramId = (string) $userRaw['id'];

            // =====================================================
            // 3. FIND OR CREATE TEYAQI USER
            // =====================================================

            $user = User::updateOrCreate(
                [
                    'telegram_id' => $telegramId,
                ],
                [
                    'name' => trim(
                        ($userRaw['first_name'] ?? '') .
                        ' ' .
                        ($userRaw['last_name'] ?? '')
                    ),

                    'username' =>
                        $userRaw['username']
                        ?? 'Warrior_' . Str::random(4),

                    'email' =>
                        $telegramId . '@teyaqi.game',

                    'password' =>
                        bcrypt(Str::random(32)),
                ]
            );

            // =====================================================
            // 4. ISSUE AUTH TOKEN
            // =====================================================

            $token = $user
                ->createToken('teyaqi')
                ->plainTextToken;

            // =====================================================
            // 5. DEBUG LOG — SAFE IDENTITY CHECK
            // =====================================================

            Log::info('Telegram authentication successful.', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'username' => $user->username,
            ]);

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);

        } catch (\Exception $e) {

            Log::error(
                'Telegram authentication failed.',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'error' => 'Authentication engine error.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}