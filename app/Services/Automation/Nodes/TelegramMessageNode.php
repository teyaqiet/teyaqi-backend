<?php

namespace App\Services\Automation\Nodes;

use App\Exceptions\TelegramPermanentException;
use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\NodeResult;
use App\Services\TelegramService;

class TelegramMessageNode
{
    public function __construct(
        protected TelegramService $telegram
    ) {
    }

    /**
     * Execute Telegram message node.
     */
    public function handle(
        AutomationExecution $execution,
        AutomationNode $node,
        array $context
    ): NodeResult {
        /*
        |--------------------------------------------------------------------------
        | Resolve configuration
        |--------------------------------------------------------------------------
        */

        $config = $node->config ?? [];

        if (!is_array($config)) {
            $config = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Message
        |--------------------------------------------------------------------------
        */

        $message = (string) (
            $config['message'] ?? ''
        );

        $message = $this->resolveVariables(
            $message,
            $context
        );

        /*
        |--------------------------------------------------------------------------
        | Recipient
        |--------------------------------------------------------------------------
        */

        $recipient = $config['recipient']
            ?? 'context.telegram_id';

        /*
        |--------------------------------------------------------------------------
        | Test mode
        |--------------------------------------------------------------------------
        */

        $testMode = (bool) (
            $context['test_mode'] ?? false
        );

        if ($testMode) {

            $testChatId = config(
                'services.telegram.test_chat_id'
            );

            if (
                empty($testChatId)
            ) {
                throw new TelegramPermanentException(
                    'Telegram test mode is enabled but no test chat ID is configured.'
                );
            }

            $chatId = $testChatId;

        } else {

            if ($recipient === 'config.chat_id') {

                $chatId = $config['chat_id']
                    ?? null;

            } else {

                $chatId = data_get(
                    $context,
                    'telegram_id'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate recipient
        |--------------------------------------------------------------------------
        */

        if (
            $chatId === null ||
            trim((string) $chatId) === ''
        ) {
            throw new TelegramPermanentException(
                'Telegram message node has no valid recipient.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Telegram options
        |--------------------------------------------------------------------------
        */

        $parseMode =
            $config['parse_mode']
            ?? 'HTML';

        if ($parseMode === '') {
            $parseMode = null;
        }

        $disableWebPagePreview = (bool) (
            $config['disable_web_page_preview']
            ?? false
        );

        $disableNotification = (bool) (
            $config['disable_notification']
            ?? false
        );

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        $buttons = $this->resolveButtons(
            $config['buttons'] ?? [],
            $context
        );

        /*
        |--------------------------------------------------------------------------
        | Media
        |--------------------------------------------------------------------------
        */

        $media = $this->resolveMedia(
            $config['media'] ?? [],
            $context
        );

        /*
        |--------------------------------------------------------------------------
        | Send
        |--------------------------------------------------------------------------
        */

        $response = $this->sendTelegramMessage(
            chatId: (string) $chatId,
            message: $message,
            media: $media,
            buttons: $buttons,
            parseMode: $parseMode,
            disableWebPagePreview: $disableWebPagePreview,
            disableNotification: $disableNotification,
        );

        /*
        |--------------------------------------------------------------------------
        | Message ID
        |--------------------------------------------------------------------------
        */

        $messageId = data_get(
            $response,
            'result.message_id'
        );

        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        return NodeResult::continue(
            output: [
                'telegram' => [
                    'status' => 'sent',

                    'chat_id' =>
                        (string) $chatId,

                    'message' =>
                        $message,

                    'message_id' =>
                        $messageId,

                    'media' =>
                        $media,

                    'buttons' =>
                        $buttons,

                    'test_mode' =>
                        $testMode,

                    'telegram' =>
                        $response,
                ],
            ]
        );
    }

    /**
     * Resolve {{variables}}.
     */
    protected function resolveVariables(
        string $value,
        array $context
    ): string {
        return preg_replace_callback(
            '/\{\{([^}]+)\}\}/',
            function ($matches) use ($context) {

                $path = trim(
                    $matches[1]
                );

                $resolved = data_get(
                    $context,
                    $path,
                    ''
                );

                if (
                    is_array($resolved) ||
                    is_object($resolved)
                ) {
                    return json_encode(
                        $resolved,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    );
                }

                if ($resolved === null) {
                    return '';
                }

                return (string) $resolved;
            },
            $value
        ) ?? $value;
    }

    /**
     * Resolve buttons.
     */
    protected function resolveButtons(
        mixed $buttons,
        array $context
    ): array {
        if (!is_array($buttons)) {
            return [];
        }

        if (
            array_key_exists('enabled', $buttons) &&
            !$buttons['enabled']
        ) {
            return [];
        }

        if (
            isset($buttons['items']) &&
            is_array($buttons['items'])
        ) {
            $items = $buttons['items'];
        } else {
            $items = $buttons;
        }

        $resolved = [];

        foreach ($items as $button) {

            if (!is_array($button)) {
                continue;
            }

            $text = $this->resolveVariables(
                (string) (
                    $button['text'] ?? ''
                ),
                $context
            );

            $url = $this->resolveVariables(
                (string) (
                    $button['url'] ?? ''
                ),
                $context
            );

            if (
                trim($text) === '' ||
                trim($url) === ''
            ) {
                continue;
            }

            $type = $button['type']
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

            $resolved[] = [
                'text' =>
                    trim($text),

                'type' =>
                    $type,

                'url' =>
                    trim($url),
            ];
        }

        return $resolved;
    }

    /**
     * Resolve media configuration.
     */
    protected function resolveMedia(
        mixed $media,
        array $context
    ): array {
        $empty = [
            'enabled' => false,
            'type' => null,
            'url' => null,
            'caption' => null,
        ];

        if (!is_array($media)) {
            return $empty;
        }

        /*
        |--------------------------------------------------------------------------
        | Media wrapper
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('enabled', $media) &&
            !$media['enabled']
        ) {
            return $empty;
        }

        /*
        |--------------------------------------------------------------------------
        | Support alternative configuration formats
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | media: {
        |     type: "photo",
        |     url: "..."
        | }
        |
        | or:
        |
        | media: {
        |     enabled: true,
        |     type: "image",
        |     file: "..."
        | }
        |
        |--------------------------------------------------------------------------
        */

        $type = strtolower(
            trim(
                (string) (
                    $media['type']
                    ?? $media['media_type']
                    ?? ''
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize media types
        |--------------------------------------------------------------------------
        */

        $typeMap = [
            'image' => 'photo',
            'picture' => 'photo',
            'jpg' => 'photo',
            'jpeg' => 'photo',
            'png' => 'photo',

            'mp4' => 'video',

            'mp3' => 'audio',

            'pdf' => 'document',
            'file' => 'document',

            'gif' => 'animation',
        ];

        if (isset($typeMap[$type])) {
            $type = $typeMap[$type];
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve media source
        |--------------------------------------------------------------------------
        */

        $source =
            $media['url']
            ?? $media['file']
            ?? $media['source']
            ?? $media['path']
            ?? $media['file_id']
            ?? '';

        /*
        |--------------------------------------------------------------------------
        | Handle nested source
        |--------------------------------------------------------------------------
        */

        if (is_array($source)) {

            $source =
                $source['url']
                ?? $source['path']
                ?? $source['file']
                ?? $source['file_id']
                ?? '';
        }

        $source = $this->resolveVariables(
            (string) $source,
            $context
        );

        /*
        |--------------------------------------------------------------------------
        | Caption
        |--------------------------------------------------------------------------
        */

        $caption = $this->resolveVariables(
            (string) (
                $media['caption']
                ?? ''
            ),
            $context
        );

        /*
        |--------------------------------------------------------------------------
        | Supported types
        |--------------------------------------------------------------------------
        */

        $supportedTypes = [
            'photo',
            'video',
            'audio',
            'document',
            'animation',
        ];

        if (
            !in_array(
                $type,
                $supportedTypes,
                true
            )
        ) {
            return $empty;
        }

        /*
        |--------------------------------------------------------------------------
        | Source required
        |--------------------------------------------------------------------------
        */

        if (
            trim($source) === ''
        ) {
            return $empty;
        }

        return [
            'enabled' => true,

            'type' =>
                $type,

            'url' =>
                trim($source),

            'caption' =>
                trim($caption) !== ''
                    ? trim($caption)
                    : null,
        ];
    }

    /**
     * Send Telegram message/media.
     */
    protected function sendTelegramMessage(
        string $chatId,
        string $message,
        array $media,
        array $buttons,
        ?string $parseMode,
        bool $disableWebPagePreview,
        bool $disableNotification
    ): array {

        /*
        |--------------------------------------------------------------------------
        | No media
        |--------------------------------------------------------------------------
        */

        if (
            !($media['enabled'] ?? false)
        ) {
            return $this->telegram->sendMessage(
                $chatId,
                $message,
                $buttons,
                $parseMode,
                $disableWebPagePreview,
                $disableNotification
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Caption
        |--------------------------------------------------------------------------
        */

        $caption =
            $media['caption']
            ?? null;

        if (
            $caption === null ||
            $caption === ''
        ) {
            $caption =
                $message !== ''
                    ? $message
                    : null;
        }

        /*
        |--------------------------------------------------------------------------
        | Photo
        |--------------------------------------------------------------------------
        */

        if (
            $media['type'] === 'photo'
        ) {
            return $this->telegram->sendPhoto(
                $chatId,
                $media['url'],
                $caption,
                $buttons,
                $parseMode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Video
        |--------------------------------------------------------------------------
        */

        if (
            $media['type'] === 'video'
        ) {
            return $this->telegram->sendVideo(
                $chatId,
                $media['url'],
                $caption,
                $buttons,
                $parseMode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Audio
        |--------------------------------------------------------------------------
        */

        if (
            $media['type'] === 'audio'
        ) {
            return $this->telegram->sendAudio(
                $chatId,
                $media['url'],
                $caption,
                $buttons,
                $parseMode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Document
        |--------------------------------------------------------------------------
        */

        if (
            $media['type'] === 'document'
        ) {
            return $this->telegram->sendDocument(
                $chatId,
                $media['url'],
                $caption,
                $buttons,
                $parseMode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Animation
        |--------------------------------------------------------------------------
        */

        if (
            $media['type'] === 'animation'
        ) {
            return $this->telegram->sendAnimation(
                $chatId,
                $media['url'],
                $caption,
                $buttons,
                $parseMode
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        return $this->telegram->sendMessage(
            $chatId,
            $message,
            $buttons,
            $parseMode,
            $disableWebPagePreview,
            $disableNotification
        );
    }
}