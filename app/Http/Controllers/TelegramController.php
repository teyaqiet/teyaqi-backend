<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
    }

    public function handleWebhook(Request $request)
    {
        $update = $request->all();

        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        if (!isset($update['message']['text'])) {
            return response()->json(['status' => 'success']);
        }

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];
        $from = $update['message']['from'];

        $user = User::where('telegram_id', $from['id'])->first();

        if (!$user) {
            $user = User::create([
                'telegram_id' => $from['id'],
                'name' => trim(
                    ($from['first_name'] ?? 'User') . ' ' .
                    ($from['last_name'] ?? '')
                ),
                'username' => $from['username'] ?? null,
                'password' => bcrypt(Str::random(16)),
                'email' => $from['id'] . '@teyaqi.com',
                'language' => 'en',
            ]);
        }

        $user->refresh();

        if ($text === '/start' || $text === '/language') {
            $this->sendLanguageSelection($chatId);
        } else {
            $this->sendUnknownCommandReply($chatId, $user);
        }

        return response()->json(['status' => 'success']);
    }

    protected function sendLanguageSelection($chatId)
    {
        $imageUrl = "https://api.lememar.com/storage/images/welcome-banner.jpg";

        $response = $this->sendTelegramRequest('sendPhoto', [
            'chat_id' => $chatId,
            'photo' => $imageUrl,
            'caption' => "Welcome to **Teyaqi**! 🇪🇹\n\nPlease select your language / እባክዎ ቋንቋ ይምረጡ፦",
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [[
                    [
                        'text' => "English 🇺🇸",
                        'callback_data' => 'setlang_en',
                    ],
                    [
                        'text' => "አማርኛ 🇪🇹",
                        'callback_data' => 'setlang_am',
                    ],
                ]],
            ],
        ]);

        if (!$response->successful()) {
            $this->sendTelegramRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "Welcome to **Teyaqi**! 🇪🇹\n\nPlease select your language / እባክዎ ቋንቋ ይምረጡ፦",
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => "English 🇺🇸",
                            'callback_data' => 'setlang_en',
                        ],
                        [
                            'text' => "አማርኛ 🇪🇹",
                            'callback_data' => 'setlang_am',
                        ],
                    ]],
                ],
            ]);
        }
    }

    protected function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data = $callback['data'];
        $fromId = $callback['from']['id'];

        $user = User::where('telegram_id', $fromId)->first();

        if ($data === 'lang_select') {
            $this->sendTelegramRequest('editMessageCaption', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'caption' => "Please select your language / እባክዎ ቋንቋ ይምረጡ፦",
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => "English 🇺🇸",
                            'callback_data' => 'setlang_en',
                        ],
                        [
                            'text' => "አማርኛ 🇪🇹",
                            'callback_data' => 'setlang_am',
                        ],
                    ]],
                ],
            ]);

            $this->sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $callback['id'],
            ]);

            return response()->json(['status' => 'success']);
        }

        if (strpos($data, 'setlang_') === 0) {
            $newLang = str_replace('setlang_', '', $data);

            if ($user) {
                $user->update([
                    'language' => $newLang,
                ]);

                $user->refresh();
            }

            $this->editPhotoToMainMenu(
                $chatId,
                $messageId,
                $user,
                $newLang
            );

            $this->sendTelegramRequest('answerCallbackQuery', [
                'callback_query_id' => $callback['id'],
                'text' => (
                    $newLang === 'am'
                        ? "ቋንቋ ተቀይሯል"
                        : "Language Updated"
                ),
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    protected function editPhotoToMainMenu($chatId, $messageId, $user, $lang)
    {
        $texts = $this->getTranslations($user, $lang);

        $playUrl = $this->getFrontendUrl($lang);

        $response = $this->sendTelegramRequest('editMessageCaption', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $texts['welcome'],
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => $texts['play'],
                            'web_app' => [
                                'url' => $playUrl,
                            ],
                        ],
                    ],
                    [
                        [
                            'text' => $texts['change_lang'],
                            'callback_data' => 'lang_select',
                        ],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Telegram editMessageCaption failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
                'play_url' => $playUrl,
            ]);
        }
    }

    protected function sendUnknownCommandReply($chatId, $user)
    {
        $lang = $user->language ?? 'en';

        $texts = $this->getTranslations($user, $lang);

        /*
        |--------------------------------------------------------------------------
        | 1. Send the small text error message
        |--------------------------------------------------------------------------
        */

        $msg = ($lang === 'am')
            ? "❓ ይቅርታ፣ ትዕዛዙን አላወቅኩትም።"
            : "❓ Sorry, I didn't recognize that command.";

        $this->sendTelegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Get frontend URL
        |--------------------------------------------------------------------------
        */

        $playUrl = $this->getFrontendUrl($lang);

        /*
        |--------------------------------------------------------------------------
        | 3. Send the full photo menu
        |--------------------------------------------------------------------------
        */

        $imageUrl = "https://api.lememar.com/storage/images/welcome-banner.jpg";

        $response = $this->sendTelegramRequest('sendPhoto', [
            'chat_id' => $chatId,
            'photo' => $imageUrl,
            'caption' => $texts['welcome'],
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => $texts['play'],
                            'web_app' => [
                                'url' => $playUrl,
                            ],
                        ],
                    ],
                    [
                        [
                            'text' => $texts['change_lang'],
                            'callback_data' => 'lang_select',
                        ],
                    ],
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Log Telegram error if sendPhoto fails
        |--------------------------------------------------------------------------
        */

        if (!$response->successful()) {
            Log::error('Telegram sendPhoto failed.', [
                'status' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
                'play_url' => $playUrl,
                'image_url' => $imageUrl,
                'chat_id' => $chatId,
            ]);
        }
    }

    protected function getFrontendUrl($lang)
    {
        $frontendUrl = config('services.frontend.url');

        if (!$frontendUrl) {
            Log::error('FRONTEND_URL is not configured.', [
                'frontend_url' => $frontendUrl,
            ]);

            return '';
        }

        return rtrim($frontendUrl, '/')
            . '?lang='
            . urlencode($lang);
    }

    protected function getTranslations($user, $lang)
    {
        $userName = $user->name ?? 'User';

        $translations = [
            'en' => [
                'welcome' => "Welcome to **Teyaqi**, $userName! 🇪🇹\nReady to test your knowledge?",
                'play' => "🎮 Play Now",
                'change_lang' => "🌐 Change Language",
            ],

            'am' => [
                'welcome' => "እንኳን ወደ **Teyaqi** በደህና መጡ $userName! 🇪🇹\nእውቀትዎን ለመፈተሽ ዝግጁ ነዎት?",
                'play' => "🎮 አሁኑኑ ይጫወቱ",
                'change_lang' => "🌐 ቋንቋ ለመቀየር",
            ],
        ];

        return $translations[$lang] ?? $translations['en'];
    }

    protected function sendTelegramRequest($method, $params)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";

        $response = Http::post($url, $params);

        if (!$response->successful()) {
            Log::error("Telegram API request failed: {$method}", [
                'status' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
            ]);
        }

        return $response;
    }
}