<?php

namespace App\Services;

use App\Exceptions\TelegramPermanentException;
use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\User;
use App\Jobs\ProcessBroadcastBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class BroadcastService
{
    public function __construct(
        protected TelegramService $telegram
    ) {
    }

    /**
     * Create a new broadcast.
     */
    public function create(
        array $data,
        ?int $createdBy = null
    ): Broadcast {
        return Broadcast::create([
            'title' => $data['title'],
            'message' => $data['message'] ?? '',

            'type' => $data['type'] ?? 'manual',
            'channel' => $data['channel'] ?? 'telegram',

            'status' => !empty($data['scheduled_at'])
                ? 'scheduled'
                : 'draft',

            'audience_type' => $data['audience_type'] ?? 'all',

            'filters' => $data['filters'] ?? null,
            'buttons' => $data['buttons'] ?? null,

            'media_type' => $data['media_type'] ?? 'none',
            'media_path' => $data['media_path'] ?? null,
            'media_caption' => $data['media_caption'] ?? null,

            'scheduled_at' => $data['scheduled_at'] ?? null,

            'created_by' => $createdBy,
        ]);
    }

    /**
     * Prepare recipients for a broadcast.
     *
     * Returns the number of newly-created recipients.
     */
    public function prepareRecipients(
        Broadcast $broadcast
    ): int {
        return DB::transaction(function () use ($broadcast) {
            $query = $this->resolveAudience($broadcast);

            $count = 0;

            $query
                ->select('id')
                ->chunkById(
                    500,
                    function ($users) use (
                        $broadcast,
                        &$count
                    ) {
                        foreach ($users as $user) {
                            $recipient = BroadcastRecipient::firstOrCreate(
                                [
                                    'broadcast_id' => $broadcast->id,
                                    'user_id' => $user->id,
                                ],
                                [
                                    'status' => 'pending',
                                    'attempts' => 0,
                                ]
                            );

                            if ($recipient->wasRecentlyCreated) {
                                $count++;
                            }
                        }
                    }
                );

            return $count;
        });
    }

    /**
     * Resolve the broadcast audience.
     */
    protected function resolveAudience(
        Broadcast $broadcast
    ): Builder {
        $query = User::query()
            ->whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '');

        switch ($broadcast->audience_type) {
            case 'all':
                break;

            case 'active':
                $this->applyActiveFilter($query);
                break;

            case 'inactive':
                $this->applyInactiveFilter($query);
                break;

            case 'filtered':
                $this->applyCustomFilters(
                    $query,
                    $broadcast->filters ?? []
                );
                break;

            default:
                throw new InvalidArgumentException(
                    "Unknown audience type: {$broadcast->audience_type}"
                );
        }

        return $query;
    }

    /**
     * Active players.
     *
     * Played within the last 7 days.
     */
    protected function applyActiveFilter(
        Builder $query
    ): void {
        $query
            ->whereNotNull('last_played_date')
            ->where(
                'last_played_date',
                '>=',
                now()->subDays(7)->toDateString()
            );
    }

    /**
     * Inactive players.
     *
     * Have not played for at least 7 days,
     * or have never played.
     */
    protected function applyInactiveFilter(
        Builder $query
    ): void {
        $query->where(function ($query) {
            $query
                ->whereNull('last_played_date')
                ->orWhere(
                    'last_played_date',
                    '<',
                    now()->subDays(7)->toDateString()
                );
        });
    }

    /**
     * Apply custom audience filters.
     */
    protected function applyCustomFilters(
        Builder $query,
        array $filters
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Total XP
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['total_xp_min'])
            && $filters['total_xp_min'] !== ''
        ) {
            $query->where(
                'total_xp',
                '>=',
                $filters['total_xp_min']
            );
        }

        if (
            isset($filters['total_xp_max'])
            && $filters['total_xp_max'] !== ''
        ) {
            $query->where(
                'total_xp',
                '<=',
                $filters['total_xp_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Weekly XP
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['weekly_xp_min'])
            && $filters['weekly_xp_min'] !== ''
        ) {
            $query->where(
                'weekly_xp',
                '>=',
                $filters['weekly_xp_min']
            );
        }

        if (
            isset($filters['weekly_xp_max'])
            && $filters['weekly_xp_max'] !== ''
        ) {
            $query->where(
                'weekly_xp',
                '<=',
                $filters['weekly_xp_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SR
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['current_sr_min'])
            && $filters['current_sr_min'] !== ''
        ) {
            $query->where(
                'current_sr',
                '>=',
                $filters['current_sr_min']
            );
        }

        if (
            isset($filters['current_sr_max'])
            && $filters['current_sr_max'] !== ''
        ) {
            $query->where(
                'current_sr',
                '<=',
                $filters['current_sr_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Current Streak
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['current_streak_min'])
            && $filters['current_streak_min'] !== ''
        ) {
            $query->where(
                'current_streak',
                '>=',
                $filters['current_streak_min']
            );
        }

        if (
            isset($filters['current_streak_max'])
            && $filters['current_streak_max'] !== ''
        ) {
            $query->where(
                'current_streak',
                '<=',
                $filters['current_streak_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Best Streak
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['best_streak_min'])
            && $filters['best_streak_min'] !== ''
        ) {
            $query->where(
                'best_streak',
                '>=',
                $filters['best_streak_min']
            );
        }

        if (
            isset($filters['best_streak_max'])
            && $filters['best_streak_max'] !== ''
        ) {
            $query->where(
                'best_streak',
                '<=',
                $filters['best_streak_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Coins
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['coins_min'])
            && $filters['coins_min'] !== ''
        ) {
            $query->where(
                'total_coins',
                '>=',
                $filters['coins_min']
            );
        }

        if (
            isset($filters['coins_max'])
            && $filters['coins_max'] !== ''
        ) {
            $query->where(
                'total_coins',
                '<=',
                $filters['coins_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lives
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['daily_lives_min'])
            && $filters['daily_lives_min'] !== ''
        ) {
            $query->where(
                'daily_lives',
                '>=',
                $filters['daily_lives_min']
            );
        }

        if (
            isset($filters['daily_lives_max'])
            && $filters['daily_lives_max'] !== ''
        ) {
            $query->where(
                'daily_lives',
                '<=',
                $filters['daily_lives_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Wins
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['wins_min'])
            && $filters['wins_min'] !== ''
        ) {
            $query->where(
                'total_wins',
                '>=',
                $filters['wins_min']
            );
        }

        if (
            isset($filters['wins_max'])
            && $filters['wins_max'] !== ''
        ) {
            $query->where(
                'total_wins',
                '<=',
                $filters['wins_max']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Language
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['language'])) {
            $query->where(
                'language',
                $filters['language']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Gender
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['gender'])) {
            $query->where(
                'gender',
                $filters['gender']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Onboarding
        |--------------------------------------------------------------------------
        */

        if (isset($filters['has_onboarded'])) {
            $query->where(
                'has_onboarded',
                (bool) $filters['has_onboarded']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Played Since
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['played_since'])) {
            $query->where(
                'last_played_date',
                '>=',
                $filters['played_since']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Played Before
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['played_before'])) {
            $query->where(
                'last_played_date',
                '<=',
                $filters['played_before']
            );
        }
    }

    /**
     * Preview how many players match a broadcast audience.
     */
    public function previewAudience(
        Broadcast $broadcast
    ): int {
        return $this
            ->resolveAudience($broadcast)
            ->count();
    }

    /**
     * Start sending a broadcast.
     *
     * The actual recipient dispatching is handled by
     * ProcessBroadcastBatch.
     *
     * This method only prepares the broadcast and starts
     * the batch coordinator.
     */
    public function send(
        Broadcast $broadcast,
        int $limit = 100
    ): array {
        if ($limit < 1) {
            throw new InvalidArgumentException(
                'Broadcast send limit must be greater than zero.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate channel
        |--------------------------------------------------------------------------
        */

        if ($broadcast->channel !== 'telegram') {
            throw new InvalidArgumentException(
                "Unsupported broadcast channel: {$broadcast->channel}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate status
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $broadcast->status,
            ['prepared', 'sending'],
            true
        )) {
            throw new InvalidArgumentException(
                "Broadcast cannot be sent while status is '{$broadcast->status}'."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare recipients
        |--------------------------------------------------------------------------
        */

        if (
            !$broadcast
                ->recipients()
                ->exists()
        ) {
            $this->prepareRecipients($broadcast);
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh broadcast
        |--------------------------------------------------------------------------
        */

        $broadcast->refresh();

        /*
        |--------------------------------------------------------------------------
        | Check whether there are recipients
        |--------------------------------------------------------------------------
        */

        $totalRecipients = $broadcast
            ->recipients()
            ->count();

        if ($totalRecipients === 0) {
            $broadcast->update([
                'status' => 'completed',
                'sent_at' => $broadcast->sent_at ?? now(),
            ]);

            return [
                'dispatched' => 0,
                'sent' => 0,
                'failed' => 0,
                'remaining' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Mark broadcast as sending
        |--------------------------------------------------------------------------
        */

        if ($broadcast->status !== 'sending') {
            $broadcast->update([
                'status' => 'sending',
            ]);

            $broadcast->refresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Count pending recipients before starting
        |--------------------------------------------------------------------------
        */

        $pendingBefore = $broadcast
            ->recipients()
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Start batch coordinator
        |--------------------------------------------------------------------------
        |
        | ProcessBroadcastBatch is responsible for:
        |
        | 1. Finding pending recipients
        | 2. Claiming the next batch
        | 3. Marking them as queued
        | 4. Dispatching SendBroadcastRecipient jobs
        | 5. Continuing with the next batch
        | 6. Finalizing the broadcast
        |
        */

        ProcessBroadcastBatch::dispatch(
    $broadcast->id,
    $limit,
    3
);

        /*
        |--------------------------------------------------------------------------
        | Return dispatch information
        |--------------------------------------------------------------------------
        |
        | Individual recipients have not necessarily been sent yet because
        | they are handled asynchronously by the queue.
        |
        */

        return [
            'dispatched' => min(
                $pendingBefore,
                $limit
            ),
            'sent' => 0,
            'failed' => 0,
            'remaining' => $pendingBefore,
        ];
    }

    /**
     * Send one broadcast recipient.
     *
     * This method is called by the queue job.
     */
    public function sendRecipient(
        BroadcastRecipient $recipient
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Load relationships
        |--------------------------------------------------------------------------
        */

        $recipient->loadMissing([
            'broadcast',
            'user',
        ]);

        $broadcast = $recipient->broadcast;
        $user = $recipient->user;

        /*
        |--------------------------------------------------------------------------
        | Validate recipient
        |--------------------------------------------------------------------------
        */

        if (!$broadcast) {
            throw new TelegramPermanentException(
                'Broadcast no longer exists.'
            );
        }

        if (!$user) {
            throw new TelegramPermanentException(
                'Recipient user no longer exists.'
            );
        }

        if (empty($user->telegram_id)) {
            throw new TelegramPermanentException(
                'User does not have a Telegram ID.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate channel
        |--------------------------------------------------------------------------
        */

        if ($broadcast->channel !== 'telegram') {
            throw new TelegramPermanentException(
                "Unsupported broadcast channel: {$broadcast->channel}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate broadcast status
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $broadcast->status,
            ['prepared', 'sending'],
            true
        )) {
            throw new TelegramPermanentException(
                "Broadcast is not active. Current status: {$broadcast->status}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve media
        |--------------------------------------------------------------------------
        */

        $mediaUrl = $this->resolveMediaUrl(
            $broadcast
        );

        /*
        |--------------------------------------------------------------------------
        | Validate media
        |--------------------------------------------------------------------------
        */

        $mediaType = $broadcast->media_type ?: 'none';

        if (
            $mediaType !== 'none'
            && empty($mediaUrl)
        ) {
            throw new TelegramPermanentException(
                'Broadcast media is configured but no valid media URL could be generated.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Personalize message
        |--------------------------------------------------------------------------
        */

        $message = $this->personalizeMessage(
            (string) ($broadcast->message ?? ''),
            $user
        );

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        $buttons = is_array($broadcast->buttons)
            ? $broadcast->buttons
            : [];

        /*
        |--------------------------------------------------------------------------
        | Send through TelegramService
        |--------------------------------------------------------------------------
        */

        $response = match ($mediaType) {
            'photo' => $this->telegram->sendPhoto(
                $user->telegram_id,
                $mediaUrl,
                $message !== ''
                    ? $message
                    : $broadcast->media_caption,
                $buttons
            ),

            'video' => $this->telegram->sendVideo(
                $user->telegram_id,
                $mediaUrl,
                $message !== ''
                    ? $message
                    : $broadcast->media_caption,
                $buttons
            ),

            'audio' => $this->telegram->sendAudio(
                $user->telegram_id,
                $mediaUrl,
                $message !== ''
                    ? $message
                    : $broadcast->media_caption,
                $buttons
            ),

            'document' => $this->telegram->sendDocument(
                $user->telegram_id,
                $mediaUrl,
                $message !== ''
                    ? $message
                    : $broadcast->media_caption,
                $buttons
            ),

            'animation' => $this->telegram->sendAnimation(
                $user->telegram_id,
                $mediaUrl,
                $message !== ''
                    ? $message
                    : $broadcast->media_caption,
                $buttons
            ),

            'none', null => $this->telegram->sendMessage(
                $user->telegram_id,
                $message,
                $buttons
            ),

            default => throw new TelegramPermanentException(
                "Unsupported broadcast media type: {$broadcast->media_type}"
            ),
        };

        /*
        |--------------------------------------------------------------------------
        | Extract Telegram message information
        |--------------------------------------------------------------------------
        */

        $telegramMessage = null;

        if (
            is_array($response)
            && isset($response['result'])
            && is_array($response['result'])
        ) {
            $telegramMessage = $response['result'];
        }

        /*
        |--------------------------------------------------------------------------
        | Return delivery information
        |--------------------------------------------------------------------------
        */

        return [
            'response' => $response,

            'delivered_at' => now(),

            'telegram_message_id' => $telegramMessage['message_id']
                ?? null,

            'telegram_chat_id' => $telegramMessage['chat']['id']
                ?? $user->telegram_id,
        ];
    }

    /**
     * Resolve the publicly accessible media URL.
     */
    protected function resolveMediaUrl(
        Broadcast $broadcast
    ): ?string {
        if (
            empty($broadcast->media_type)
            || $broadcast->media_type === 'none'
        ) {
            return null;
        }

        if (!empty($broadcast->media_url)) {
            return $broadcast->media_url;
        }

        if (empty($broadcast->media_path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($broadcast->media_path)) {
            throw new TelegramPermanentException(
                "Broadcast media file does not exist: {$broadcast->media_path}"
            );
        }

        return $disk->url(
            $broadcast->media_path
        );
    }

    /**
     * Finalize a broadcast based on recipient results.
     */
    public function finalizeBroadcast(
        Broadcast $broadcast
    ): void {
        $broadcast->refresh();

        /*
        |--------------------------------------------------------------------------
        | Active recipients
        |--------------------------------------------------------------------------
        |
        | queued is also active because those recipients have already
        | been claimed by the batch coordinator.
        |
        */

        $active = $broadcast
            ->recipients()
            ->whereIn(
                'status',
                [
                    'pending',
                    'queued',
                    'sending',
                ]
            )
            ->exists();

        if ($active) {
            if ($broadcast->status !== 'sending') {
                $broadcast->update([
                    'status' => 'sending',
                ]);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Failed recipients
        |--------------------------------------------------------------------------
        */

        $failed = $broadcast
            ->recipients()
            ->where('status', 'failed')
            ->exists();

        if ($failed) {
            $broadcast->update([
                'status' => 'failed',
                'sent_at' => $broadcast->sent_at ?? now(),
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Everything succeeded
        |--------------------------------------------------------------------------
        */

        $broadcast->update([
            'status' => 'completed',
            'sent_at' => $broadcast->sent_at ?? now(),
        ]);
    }

    /**
     * Personalize a broadcast message.
     *
     * Supported variables:
     *
     * {{name}}
     * {{username}}
     * {{first_name}}
     * {{user_id}}
     * {{telegram_id}}
     */
    protected function personalizeMessage(
        string $message,
        User $user
    ): string {
        $name = trim(
            (string) ($user->name ?? '')
        );

        if ($name === '') {
            $name = 'Player';
        }

        $username = $user->telegram_username
            ?? $user->username
            ?? '';

        $firstName = $this->extractFirstName(
            $name
        );

        return str_replace(
            [
                '{{name}}',
                '{{username}}',
                '{{first_name}}',
                '{{user_id}}',
                '{{telegram_id}}',
            ],
            [
                $name,
                $username,
                $firstName,
                (string) $user->id,
                (string) $user->telegram_id,
            ],
            $message
        );
    }

    /**
     * Extract the first name from a user's name.
     */
    protected function extractFirstName(
        string $name
    ): string {
        $name = trim($name);

        if ($name === '') {
            return 'Player';
        }

        $parts = preg_split(
            '/\s+/',
            $name
        );

        return $parts[0] ?? 'Player';
    }

    /**
     * Get broadcast statistics.
     */
    public function statistics(
        Broadcast $broadcast
    ): array {
        $query = $broadcast->recipients();

        return [
            'total' => (clone $query)->count(),

            'pending' => (clone $query)
                ->where('status', 'pending')
                ->count(),

            'queued' => (clone $query)
                ->where('status', 'queued')
                ->count(),

            'sending' => (clone $query)
                ->where('status', 'sending')
                ->count(),

            'sent' => (clone $query)
                ->where('status', 'sent')
                ->count(),

            'failed' => (clone $query)
                ->where('status', 'failed')
                ->count(),
        ];
    }
}