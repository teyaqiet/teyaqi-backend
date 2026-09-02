<?php

namespace App\Services;

use App\Exceptions\TelegramPermanentException;
use App\Exceptions\TelegramRetryableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramService
{
    protected string $botToken;

    protected string $baseUrl;

    protected TelegramRateLimiter $rateLimiter;

    public function __construct(
        TelegramRateLimiter $rateLimiter
    ) {
        $this->rateLimiter = $rateLimiter;

        $this->botToken = (string) config(
            'services.telegram.bot_token'
        );

        $this->baseUrl =
            "https://api.telegram.org/bot{$this->botToken}";
    }

    /*
    |--------------------------------------------------------------------------
    | TEXT MESSAGE
    |--------------------------------------------------------------------------
    */

    public function sendMessage(
        string|int $chatId,
        string $message,
        array $buttons = [],
        ?string $parseMode = 'HTML',
        bool $disableWebPagePreview = false,
        bool $disableNotification = false
    ): array {

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if (
            $parseMode !== null &&
            $parseMode !== ''
        ) {
            $data['parse_mode'] = $parseMode;
        }

        if ($disableWebPagePreview) {
            $data['disable_web_page_preview'] = true;
        }

        if ($disableNotification) {
            $data['disable_notification'] = true;
        }

        return $this->sendRequest(
            'sendMessage',
            $data,
            $buttons
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PHOTO
    |--------------------------------------------------------------------------
    */

    public function sendPhoto(
        string|int $chatId,
        string $photo,
        ?string $caption = null,
        array $buttons = [],
        ?string $parseMode = 'HTML'
    ): array {

        return $this->sendMedia(
            method: 'sendPhoto',
            field: 'photo',
            chatId: $chatId,
            source: $photo,
            caption: $caption,
            buttons: $buttons,
            parseMode: $parseMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIDEO
    |--------------------------------------------------------------------------
    */

    public function sendVideo(
        string|int $chatId,
        string $video,
        ?string $caption = null,
        array $buttons = [],
        ?string $parseMode = 'HTML'
    ): array {

        return $this->sendMedia(
            method: 'sendVideo',
            field: 'video',
            chatId: $chatId,
            source: $video,
            caption: $caption,
            buttons: $buttons,
            parseMode: $parseMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUDIO
    |--------------------------------------------------------------------------
    */

    public function sendAudio(
        string|int $chatId,
        string $audio,
        ?string $caption = null,
        array $buttons = [],
        ?string $parseMode = 'HTML'
    ): array {

        return $this->sendMedia(
            method: 'sendAudio',
            field: 'audio',
            chatId: $chatId,
            source: $audio,
            caption: $caption,
            buttons: $buttons,
            parseMode: $parseMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function sendDocument(
        string|int $chatId,
        string $document,
        ?string $caption = null,
        array $buttons = [],
        ?string $parseMode = 'HTML'
    ): array {

        return $this->sendMedia(
            method: 'sendDocument',
            field: 'document',
            chatId: $chatId,
            source: $document,
            caption: $caption,
            buttons: $buttons,
            parseMode: $parseMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ANIMATION / GIF
    |--------------------------------------------------------------------------
    */

    public function sendAnimation(
        string|int $chatId,
        string $animation,
        ?string $caption = null,
        array $buttons = [],
        ?string $parseMode = 'HTML'
    ): array {

        return $this->sendMedia(
            method: 'sendAnimation',
            field: 'animation',
            chatId: $chatId,
            source: $animation,
            caption: $caption,
            buttons: $buttons,
            parseMode: $parseMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA SENDER
    |--------------------------------------------------------------------------
    */

    protected function sendMedia(
        string $method,
        string $field,
        string|int $chatId,
        string $source,
        ?string $caption,
        array $buttons,
        ?string $parseMode = 'HTML'
    ): array {

        $data = [
            'chat_id' => $chatId,
        ];

        if (
            $caption !== null &&
            $caption !== ''
        ) {
            $data['caption'] = $caption;

            if (
                $parseMode !== null &&
                $parseMode !== ''
            ) {
                $data['parse_mode'] = $parseMode;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine source type
        |--------------------------------------------------------------------------
        |
        | Telegram accepts:
        |
        | 1. Telegram file_id
        | 2. Public HTTP/HTTPS URL
        | 3. Uploaded multipart file
        |
        |--------------------------------------------------------------------------
        */

        $source = trim($source);

        /*
        |--------------------------------------------------------------------------
        | Public URL or Telegram file_id
        |--------------------------------------------------------------------------
        */

        if (
            $this->isRemoteSource($source) ||
            $this->looksLikeTelegramFileId($source)
        ) {

            $data[$field] = $source;

            return $this->sendRequest(
                $method,
                $data,
                $buttons
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Local file
        |--------------------------------------------------------------------------
        */

        $localPath = $this->resolveLocalFile(
            $source
        );

        if (
            $localPath === null
        ) {
            throw new TelegramPermanentException(
                "Telegram media file could not be found: {$source}"
            );
        }

        return $this->sendMultipartRequest(
            method: $method,
            data: $data,
            field: $field,
            filePath: $localPath,
            buttons: $buttons
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STANDARD REQUEST
    |--------------------------------------------------------------------------
    */

    protected function sendRequest(
        string $method,
        array $data,
        array $buttons = []
    ): array {

        if ($this->botToken === '') {
            throw new TelegramPermanentException(
                'Telegram bot token is not configured.'
            );
        }

        $keyboard =
            $this->buildInlineKeyboard(
                $buttons
            );

        if (!empty($keyboard)) {
            $data['reply_markup'] =
                json_encode(
                    [
                        'inline_keyboard' =>
                            $keyboard,
                    ],
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE |
                    JSON_THROW_ON_ERROR
                );
        }

        $this->rateLimiter->wait();

        try {

            $response = Http::timeout(60)
                ->acceptJson()
                ->post(
                    "{$this->baseUrl}/{$method}",
                    $data
                );

        } catch (ConnectionException $e) {

            throw new TelegramRetryableException(
                'Telegram connection failed: ' .
                $e->getMessage(),
                null,
                0,
                $e
            );

        } catch (Throwable $e) {

            throw new TelegramRetryableException(
                'Telegram request failed: ' .
                $e->getMessage(),
                null,
                0,
                $e
            );
        }

        return $this->processResponse(
            $response
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MULTIPART FILE REQUEST
    |--------------------------------------------------------------------------
    */

    protected function sendMultipartRequest(
        string $method,
        array $data,
        string $field,
        string $filePath,
        array $buttons = []
    ): array {

        if ($this->botToken === '') {
            throw new TelegramPermanentException(
                'Telegram bot token is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Keyboard
        |--------------------------------------------------------------------------
        */

        $keyboard =
            $this->buildInlineKeyboard(
                $buttons
            );

        if (!empty($keyboard)) {
            $data['reply_markup'] =
                json_encode(
                    [
                        'inline_keyboard' =>
                            $keyboard,
                    ],
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE |
                    JSON_THROW_ON_ERROR
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate file
        |--------------------------------------------------------------------------
        */

        if (
            !is_file($filePath) ||
            !is_readable($filePath)
        ) {
            throw new TelegramPermanentException(
                "Telegram media file is not readable: {$filePath}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MIME type
        |--------------------------------------------------------------------------
        */

        $mimeType =
            mime_content_type($filePath)
            ?: 'application/octet-stream';

        $fileName =
            basename($filePath);

        /*
        |--------------------------------------------------------------------------
        | Rate limiter
        |--------------------------------------------------------------------------
        */

        $this->rateLimiter->wait();

        try {

            $response = Http::timeout(120)
                ->acceptJson()
                ->attach(
                    $field,
                    fopen($filePath, 'r'),
                    $fileName,
                    [
                        'Content-Type' =>
                            $mimeType,
                    ]
                )
                ->post(
                    "{$this->baseUrl}/{$method}",
                    $data
                );

        } catch (ConnectionException $e) {

            throw new TelegramRetryableException(
                'Telegram media upload connection failed: ' .
                $e->getMessage(),
                null,
                0,
                $e
            );

        } catch (Throwable $e) {

            throw new TelegramRetryableException(
                'Telegram media upload failed: ' .
                $e->getMessage(),
                null,
                0,
                $e
            );
        }

        return $this->processResponse(
            $response
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SOURCE HELPERS
    |--------------------------------------------------------------------------
    */

    protected function isRemoteSource(
        string $source
    ): bool {

        return (bool) preg_match(
            '/^https?:\/\//i',
            $source
        );
    }

    protected function looksLikeTelegramFileId(
        string $source
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Telegram file IDs are opaque strings.
        |--------------------------------------------------------------------------
        |
        | We intentionally accept reasonably long non-path strings as
        | possible Telegram file IDs.
        |
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($source, '/') ||
            str_contains($source, '\\')
        ) {
            return false;
        }

        return strlen($source) >= 20;
    }

    protected function resolveLocalFile(
        string $source
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | Absolute path
        |--------------------------------------------------------------------------
        */

        if (
            is_file($source)
        ) {
            return realpath($source) ?: $source;
        }

        /*
        |--------------------------------------------------------------------------
        | Laravel storage path
        |--------------------------------------------------------------------------
        */

        $storagePath =
            storage_path(
                'app/' . ltrim($source, '/\\')
            );

        if (
            is_file($storagePath)
        ) {
            return realpath($storagePath)
                ?: $storagePath;
        }

        /*
        |--------------------------------------------------------------------------
        | storage/app/public
        |--------------------------------------------------------------------------
        */

        $publicStoragePath =
            storage_path(
                'app/public/' .
                ltrim($source, '/\\')
            );

        if (
            is_file($publicStoragePath)
        ) {
            return realpath(
                $publicStoragePath
            ) ?: $publicStoragePath;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSE PROCESSING
    |--------------------------------------------------------------------------
    */

    protected function processResponse(
        Response $response
    ): array {

        $status =
            $response->status();

        $result =
            $response->json();

        /*
        |--------------------------------------------------------------------------
        | HTTP failure
        |--------------------------------------------------------------------------
        */

        if (!$response->successful()) {

            $description =
                $this->getErrorDescription(
                    $response,
                    $result
                );

            if ($status === 429) {

                $retryAfter =
                    $this->getRetryAfter(
                        $result
                    );

                throw new TelegramRetryableException(
                    "Telegram rate limit: {$description}",
                    $retryAfter,
                    429
                );
            }

            if (
                $this->isRetryableHttpStatus(
                    $status
                )
            ) {

                throw new TelegramRetryableException(
                    "Telegram temporary error ({$status}): {$description}",
                    null,
                    $status
                );
            }

            throw new TelegramPermanentException(
                "Telegram API error ({$status}): {$description}",
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Invalid response
        |--------------------------------------------------------------------------
        */

        if (!is_array($result)) {

            throw new TelegramRetryableException(
                'Telegram returned an invalid response.',
                null,
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Telegram API failure
        |--------------------------------------------------------------------------
        */

        if (
            ($result['ok'] ?? false) !== true
        ) {

            $description =
                $result['description']
                ?? 'Unknown Telegram API error.';

            $errorCode =
                (int) (
                    $result['error_code']
                    ?? $status
                );

            if ($errorCode === 429) {

                $retryAfter =
                    $this->getRetryAfter(
                        $result
                    );

                throw new TelegramRetryableException(
                    "Telegram rate limit: {$description}",
                    $retryAfter,
                    429
                );
            }

            if (
                $this->isRetryableHttpStatus(
                    $errorCode
                )
            ) {

                throw new TelegramRetryableException(
                    "Telegram temporary error ({$errorCode}): {$description}",
                    null,
                    $errorCode
                );
            }

            throw new TelegramPermanentException(
                "Telegram API error ({$errorCode}): {$description}",
                $errorCode
            );
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR HELPERS
    |--------------------------------------------------------------------------
    */

    protected function getErrorDescription(
        Response $response,
        mixed $result
    ): string {

        if (
            is_array($result) &&
            !empty($result['description'])
        ) {
            return (string) $result['description'];
        }

        $body =
            trim(
                $response->body()
            );

        return $body !== ''
            ? $body
            : 'Unknown Telegram API error.';
    }

    protected function getRetryAfter(
        mixed $result
    ): ?int {

        if (!is_array($result)) {
            return null;
        }

        $retryAfter =
            $result['parameters']['retry_after']
            ?? null;

        if ($retryAfter === null) {
            return null;
        }

        return max(
            1,
            (int) $retryAfter
        );
    }

    protected function isRetryableHttpStatus(
        int $status
    ): bool {

        return in_array(
            $status,
            [
                408,
                425,
                429,
                500,
                502,
                503,
                504,
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INLINE KEYBOARD
    |--------------------------------------------------------------------------
    */

    protected function buildInlineKeyboard(
        array $buttons
    ): array {

        if (empty($buttons)) {
            return [];
        }

        $keyboard = [];

        foreach ($buttons as $button) {

            if (!is_array($button)) {
                continue;
            }

            if (
                empty($button['text']) ||
                empty($button['url'])
            ) {
                continue;
            }

            $type =
                $button['type']
                ?? 'url';

            if (
                !in_array(
                    $type,
                    [
                        'url',
                        'web_app',
                    ],
                    true
                )
            ) {
                continue;
            }

            $text =
                trim(
                    (string) $button['text']
                );

            $url =
                trim(
                    (string) $button['url']
                );

            if (
                $text === '' ||
                $url === ''
            ) {
                continue;
            }

            $buttonPayload = [
                'text' => $text,
            ];

            if ($type === 'web_app') {

                $buttonPayload['web_app'] = [
                    'url' => $url,
                ];

            } else {

                $buttonPayload['url'] =
                    $url;
            }

            $keyboard[] = [
                $buttonPayload,
            ];
        }

        return $keyboard;
    }
}