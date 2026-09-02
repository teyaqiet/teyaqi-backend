<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\User;
use App\Services\BroadcastService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendBroadcastRecipient;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    /**
     * Display the broadcast list.
     */
    public function index(): View
    {
        $broadcasts = Broadcast::query()
            ->latest()
            ->paginate(20);

        return view('admin.broadcasts.index', compact('broadcasts'));
    }

    /**
     * Show the create broadcast form.
     */
    public function create(): View
    {
        return view('admin.broadcasts.create');
    }

    /**
     * Store a new broadcast.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBroadcast($request);

        $filters = $this->cleanFilters($validated);
        $buttons = $this->cleanButtons($validated);

        $status = !empty($validated['scheduled_at'])
            ? 'scheduled'
            : 'draft';

        /*
        |--------------------------------------------------------------------------
        | Handle uploaded media
        |--------------------------------------------------------------------------
        */

        $mediaPath = null;

        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store(
                'broadcasts',
                'public'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create broadcast
        |--------------------------------------------------------------------------
        */

        $broadcast = Broadcast::create([
            'title' => $validated['title'],
            'message' => $validated['message'],

            'media_type' => $validated['media_type'] ?? 'none',
            'media_path' => $mediaPath,
            'media_caption' => $validated['media_caption'] ?? null,

            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'status' => $status,

            'audience_type' => $validated['audience_type'],

            'filters' => $filters ?: null,
            'buttons' => $buttons ?: null,

            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'sent_at' => null,

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            | broadcasts.created_by currently references users.id.
            | auth()->id() is the admin_users.id, so do NOT save it here.
            |
            | Until the foreign key is changed to admin_users, keep this NULL.
            |--------------------------------------------------------------------------
            */

            'created_by' => null,
        ]);

        return redirect()
            ->route('admin.broadcasts.index')
            ->with(
                'success',
                "Broadcast \"{$broadcast->title}\" was created successfully."
            );
    }

    /**
     * Show the edit broadcast form.
     */
    public function edit(Broadcast $broadcast): View|RedirectResponse
    {
        if (!in_array($broadcast->status, [
            'draft',
            'scheduled',
            'prepared',
        ], true)) {
            return redirect()
                ->route('admin.broadcasts.show', $broadcast)
                ->with(
                    'error',
                    'This broadcast cannot be edited in its current status.'
                );
        }

        return view('admin.broadcasts.edit', compact('broadcast'));
    }

    /**
     * Update an existing broadcast.
     */
    public function update(
        Request $request,
        Broadcast $broadcast
    ): RedirectResponse {
        if (!in_array($broadcast->status, [
            'draft',
            'scheduled',
            'prepared',
        ], true)) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'This broadcast cannot be modified in its current status.'
                );
        }

        $validated = $this->validateBroadcast($request);

        $filters = $this->cleanFilters($validated);
        $buttons = $this->cleanButtons($validated);

        /*
        |--------------------------------------------------------------------------
        | Handle media
        |--------------------------------------------------------------------------
        |
        | If a new media file is uploaded:
        |   - store the new file
        |   - remember the old path
        |   - replace media_path
        |
        | If no new file is uploaded:
        |   - keep the existing media_path
        |
        |--------------------------------------------------------------------------
        */

        $mediaPath = $broadcast->media_path;
        $oldMediaPath = null;

        if ($request->hasFile('media')) {
            $oldMediaPath = $broadcast->media_path;

            $mediaPath = $request->file('media')->store(
                'broadcasts',
                'public'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Handle explicit media removal
        |--------------------------------------------------------------------------
        |
        | If media_type is "none", remove the existing media reference.
        |
        |--------------------------------------------------------------------------
        */

        if (($validated['media_type'] ?? 'none') === 'none') {
            $oldMediaPath = $broadcast->media_path;
            $mediaPath = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Determine whether the audience changed
        |--------------------------------------------------------------------------
        */

        $oldFilters = $this->normalizeArray(
            $broadcast->filters ?? []
        );

        $newFilters = $this->normalizeArray($filters);

        $audienceChanged =
            $broadcast->audience_type !== $validated['audience_type']
            || $oldFilters !== $newFilters;

        /*
        |--------------------------------------------------------------------------
        | Determine new status
        |--------------------------------------------------------------------------
        */

        $status = $this->determineUpdateStatus(
            $broadcast,
            $validated,
            $audienceChanged
        );

        /*
        |--------------------------------------------------------------------------
        | Update broadcast
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $broadcast,
            $validated,
            $filters,
            $buttons,
            $status,
            $audienceChanged,
            $mediaPath
        ) {
            /*
            |--------------------------------------------------------------------------
            | Prepared broadcast + changed audience
            |--------------------------------------------------------------------------
            |
            | Existing recipients are no longer valid.
            |
            */

            if (
                $broadcast->status === 'prepared'
                && $audienceChanged
            ) {
                $broadcast->recipients()->delete();
            }

            $broadcast->update([
                'title' => $validated['title'],
                'message' => $validated['message'],

                'media_type' => $validated['media_type'] ?? 'none',
                'media_path' => $mediaPath,
                'media_caption' => $validated['media_caption'] ?? null,

                'type' => $validated['type'],
                'channel' => $validated['channel'],
                'status' => $status,

                'audience_type' => $validated['audience_type'],

                'filters' => $filters ?: null,
                'buttons' => $buttons ?: null,

                'scheduled_at' => $validated['scheduled_at'] ?? null,

                'sent_at' => in_array($status, [
                    'draft',
                    'scheduled',
                    'prepared',
                ], true)
                    ? null
                    : $broadcast->sent_at,
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Delete old media file
        |--------------------------------------------------------------------------
        |
        | Only delete the old file after the database transaction succeeds.
        |
        |--------------------------------------------------------------------------
        */

        if (
            $oldMediaPath !== null
            && $oldMediaPath !== $mediaPath
            && Storage::disk('public')->exists($oldMediaPath)
        ) {
            Storage::disk('public')->delete($oldMediaPath);
        }

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        $message = $audienceChanged && $status === 'draft'
            ? "Broadcast \"{$broadcast->title}\" was updated. The audience must be prepared again."
            : "Broadcast \"{$broadcast->title}\" was updated successfully.";

        return redirect()
            ->route('admin.broadcasts.show', $broadcast)
            ->with('success', $message);
    }

    /**
     * Display a broadcast.
     */
    public function show(Broadcast $broadcast): View
    {
        $broadcast->loadCount([
            'recipients',

            'recipients as pending_recipients_count' => function ($query) {
                $query->where('status', 'pending');
            },

            'recipients as sent_recipients_count' => function ($query) {
                $query->where('status', 'sent');
            },

            'recipients as failed_recipients_count' => function ($query) {
                $query->where('status', 'failed');
            },
        ]);

        $total = (int) ($broadcast->recipients_count ?? 0);

        $processed =
            (int) ($broadcast->sent_recipients_count ?? 0)
            + (int) ($broadcast->failed_recipients_count ?? 0);

        $progress = $total > 0
            ? round(($processed / $total) * 100, 1)
            : 0;

        $recipients = $broadcast->recipients()
            ->with('user')
            ->latest()
            ->paginate(25);

        return view('admin.broadcasts.show', [
            'broadcast' => $broadcast,
            'recipients' => $recipients,
            'total' => $total,
            'processed' => $processed,
            'progress' => $progress,
        ]);
    }


    /**
 * Return live broadcast delivery progress.
 */
public function progress(Broadcast $broadcast): JsonResponse
{
    $total = $broadcast->recipients()->count();

    $sent = $broadcast->recipients()
        ->where('status', 'sent')
        ->count();

    $failed = $broadcast->recipients()
        ->where('status', 'failed')
        ->count();

    $pending = $broadcast->recipients()
        ->where('status', 'pending')
        ->count();

    $processing = $broadcast->recipients()
        ->whereIn('status', [
            'processing',
            'sending',
        ])
        ->count();

    $processed = $sent + $failed;

    $progress = $total > 0
        ? round(($processed / $total) * 100, 1)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Automatically resolve broadcast status
    |--------------------------------------------------------------------------
    */

    if (
        $total > 0
        && $pending === 0
        && $processing === 0
    ) {
        if ($failed > 0 && $sent < $total) {
            if ($broadcast->status !== 'failed') {
                $broadcast->update([
                    'status' => 'failed',
                ]);
            }
        } elseif ($sent === $total) {
            if ($broadcast->status !== 'completed') {
                $broadcast->update([
                    'status' => 'completed',
                    'sent_at' => $broadcast->sent_at ?? now(),
                ]);
            }
        }
    }

    return response()->json([
        'success' => true,

        'broadcast' => [
            'id' => $broadcast->id,
            'status' => $broadcast->fresh()->status,
        ],

        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'processing' => $processing,
        'processed' => $processed,
        'progress' => $progress,

        'completed' => $total > 0
            && $pending === 0
            && $processing === 0,

        'finished' => in_array(
            $broadcast->fresh()->status,
            ['completed', 'failed'],
            true
        ),
    ]);
}


    /**
     * Preview the number of players matching a broadcast audience.
     */
    public function audiencePreview(Request $request): JsonResponse
    {
        $validated = $this->validateAudience($request);

        $query = $this->buildAudienceQuery(
            $validated['audience_type'],
            $validated['filters'] ?? [],
            $validated['channel'] ?? 'telegram'
        );

        return response()->json([
            'success' => true,
            'count' => $query->count(),
        ]);
    }

    /**
     * Prepare recipients for a broadcast.
     */
    public function prepare(Broadcast $broadcast): RedirectResponse
    {
        if (!in_array($broadcast->status, [
            'draft',
            'scheduled',
        ], true)) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'This broadcast cannot be prepared in its current status.'
                );
        }

        $query = $this->buildAudienceQuery(
            $broadcast->audience_type,
            $broadcast->filters ?? [],
            $broadcast->channel
        );

        /*
        |--------------------------------------------------------------------------
        | Avoid duplicate recipients
        |--------------------------------------------------------------------------
        */

        $existingUserIds = $broadcast->recipients()
            ->pluck('user_id');

        if ($existingUserIds->isNotEmpty()) {
            $query->whereNotIn('id', $existingUserIds);
        }

        $recipientCount = 0;

        DB::transaction(function () use (
            $query,
            $broadcast,
            &$recipientCount
        ) {
            $query
                ->select('id')
                ->chunkById(500, function ($users) use (
                    $broadcast,
                    &$recipientCount
                ) {
                    $now = now();
                    $rows = [];

                    foreach ($users as $user) {
                        $rows[] = [
                            'broadcast_id' => $broadcast->id,
                            'user_id' => $user->id,
                            'status' => 'pending',
                            'attempts' => 0,
                            'sent_at' => null,
                            'delivered_at' => null,
                            'error_message' => null,
                            'response' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $recipientCount++;
                    }

                    if (!empty($rows)) {
                        BroadcastRecipient::insert($rows);
                    }
                });

            $broadcast->update([
                'status' => 'prepared',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | No recipients found
        |--------------------------------------------------------------------------
        */

        if (
            $recipientCount === 0
            && !$broadcast->recipients()->exists()
        ) {
            $broadcast->update([
                'status' => 'draft',
            ]);

            return redirect()
                ->route('admin.broadcasts.show', $broadcast)
                ->with(
                    'error',
                    'No players matched this audience. Please review the audience settings.'
                );
        }

        return redirect()
            ->route('admin.broadcasts.show', $broadcast)
            ->with(
                'success',
                "{$recipientCount} new recipients were prepared successfully."
            );
    }

    /**
     * Send a broadcast.
     */
    public function send(
    Broadcast $broadcast
): RedirectResponse {
    if (!in_array($broadcast->status, [
        'prepared',
        'sending',
    ], true)) {
        return redirect()
            ->back()
            ->with(
                'error',
                'This broadcast is not ready to be sent.'
            );
    }

    $pendingRecipients = $broadcast->recipients()
        ->where('status', 'pending')
        ->pluck('id');

    if ($pendingRecipients->isEmpty()) {
        $failedCount = $broadcast->recipients()
            ->where('status', 'failed')
            ->count();

        if ($failedCount > 0) {
            $broadcast->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route(
                    'admin.broadcasts.show',
                    $broadcast
                )
                ->with(
                    'error',
                    'There are no pending recipients. Some recipients have failed.'
                );
        }

        $broadcast->update([
            'status' => 'completed',
            'sent_at' => $broadcast->sent_at ?? now(),
        ]);

        return redirect()
            ->route(
                'admin.broadcasts.show',
                $broadcast
            )
            ->with(
                'success',
                'This broadcast has already been completely delivered.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark broadcast as sending
    |--------------------------------------------------------------------------
    */

    $broadcast->update([
        'status' => 'sending',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Dispatch recipient jobs
    |--------------------------------------------------------------------------
    */

    foreach ($pendingRecipients as $recipientId) {
        SendBroadcastRecipient::dispatch(
            $recipientId
        )->onQueue('broadcasts');
    }

    return redirect()
        ->route(
            'admin.broadcasts.show',
            $broadcast
        )
        ->with(
            'success',
            $pendingRecipients->count()
            . ' broadcast messages have been queued for delivery.'
        );
}

    /**
     * Retry failed recipients.
     */
    public function retryFailed(
        Broadcast $broadcast,
        BroadcastService $broadcastService
    ): RedirectResponse {
        if (!in_array($broadcast->status, [
            'failed',
            'completed',
            'sending',
            'prepared',
        ], true)) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Failed recipients cannot be retried in the current broadcast status.'
                );
        }

        $failedCount = $broadcast->recipients()
            ->where('status', 'failed')
            ->count();

        if ($failedCount === 0) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'There are no failed recipients to retry.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Reset failed recipients
        |--------------------------------------------------------------------------
        */

        $broadcast->recipients()
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'updated_at' => now(),
            ]);

        $broadcast->update([
            'status' => 'sending',
            'sent_at' => null,
        ]);

        $result = $broadcastService->send(
            $broadcast,
            100
        );

        return redirect()
            ->route('admin.broadcasts.show', $broadcast)
            ->with(
                'success',
                "{$result['sent']} messages retried successfully, "
                . "{$result['failed']} failed, "
                . "{$result['remaining']} remaining."
            );
    }

    /**
     * Validate a broadcast request.
     */
    protected function validateBroadcast(Request $request): array
    {
        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'message' => [
                'required',
                'string',
            ],

            'type' => [
                'required',
                'in:manual,automation',
            ],

            'channel' => [
                'required',
                'in:telegram',
            ],

            'audience_type' => [
                'required',
                'in:all,active,inactive,filtered',
            ],

            'filters' => [
                'nullable',
                'array',
            ],

            'filters.total_xp_min' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.total_xp_max' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.current_sr_min' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'filters.current_sr_max' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'filters.current_streak_min' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.current_streak_max' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.language' => [
                'nullable',
                'string',
                'in:am,en',
            ],

            'filters.has_onboarded' => [
                'nullable',
                'boolean',
            ],

            'scheduled_at' => [
                'nullable',
                'date',
                'after_or_equal:now',
            ],

            'media_type' => [
                'nullable',
                'in:none,photo,video,audio,document',
            ],

            'media' => [
                'nullable',
                'file',
                'max:51200',
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,mp3,wav,ogg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip',
            ],

            'media_caption' => [
                'nullable',
                'string',
                'max:1024',
            ],

            /*
            |--------------------------------------------------------------------------
            | Telegram Buttons
            |--------------------------------------------------------------------------
            */

            'buttons' => [
                'nullable',
                'array',
                'max:10',
            ],

            'buttons.*' => [
                'array',
            ],

            'buttons.*.text' => [
                'required',
                'string',
                'max:64',
            ],

            'buttons.*.type' => [
                'required',
                'in:url,web_app',
            ],

            'buttons.*.url' => [
                'required',
                'url',
                'max:2048',
            ],
        ]);
    }

    /**
     * Validate audience preview request.
     */
    protected function validateAudience(Request $request): array
    {
        return $request->validate([
            'audience_type' => [
                'required',
                'in:all,active,inactive,filtered',
            ],

            'channel' => [
                'nullable',
                'in:telegram',
            ],

            'filters' => [
                'nullable',
                'array',
            ],

            'filters.total_xp_min' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.total_xp_max' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.current_sr_min' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'filters.current_sr_max' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'filters.current_streak_min' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.current_streak_max' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'filters.language' => [
                'nullable',
                'string',
                'in:am,en',
            ],

            'filters.has_onboarded' => [
                'nullable',
                'boolean',
            ],
        ]);
    }

    /**
     * Build the audience query.
     *
     * This is intentionally shared by audiencePreview() and prepare()
     * so both operations always calculate the same audience.
     */
    protected function buildAudienceQuery(
        string $audienceType,
        array $filters = [],
        string $channel = 'telegram'
    ): Builder {
        $query = User::query();

        /*
        |--------------------------------------------------------------------------
        | Audience type
        |--------------------------------------------------------------------------
        */

        if ($audienceType === 'active') {
            $query->whereDate(
                'last_played_date',
                '>=',
                Carbon::today()->subDays(7)
            );
        }

        if ($audienceType === 'inactive') {
            $query->where(function ($query) {
                $query
                    ->whereNull('last_played_date')
                    ->orWhereDate(
                        'last_played_date',
                        '<',
                        Carbon::today()->subDays(7)
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered audience
        |--------------------------------------------------------------------------
        */

        if ($audienceType === 'filtered') {
            $this->applyAudienceFilters(
                $query,
                $filters
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Telegram requires a Telegram ID.
        |--------------------------------------------------------------------------
        */

        if ($channel === 'telegram') {
            $query
                ->whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '');
        }

        return $query;
    }

    /**
     * Apply filtered-audience conditions.
     */
    protected function applyAudienceFilters(
        Builder $query,
        array $filters
    ): void {
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

        if (
            isset($filters['language'])
            && $filters['language'] !== ''
        ) {
            $query->where(
                'language',
                $filters['language']
            );
        }

        if (
            array_key_exists('has_onboarded', $filters)
            && $filters['has_onboarded'] !== ''
            && $filters['has_onboarded'] !== null
        ) {
            $query->where(
                'has_onboarded',
                (bool) $filters['has_onboarded']
            );
        }
    }

    /**
     * Determine status after an update.
     */
    protected function determineUpdateStatus(
        Broadcast $broadcast,
        array $validated,
        bool $audienceChanged
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Prepared broadcast with changed audience
        |--------------------------------------------------------------------------
        */

        if (
            $broadcast->status === 'prepared'
            && $audienceChanged
        ) {
            return 'draft';
        }

        /*
        |--------------------------------------------------------------------------
        | Prepared broadcast without audience changes
        |--------------------------------------------------------------------------
        */

        if ($broadcast->status === 'prepared') {
            return 'prepared';
        }

        /*
        |--------------------------------------------------------------------------
        | Draft / scheduled
        |--------------------------------------------------------------------------
        */

        return !empty($validated['scheduled_at'])
            ? 'scheduled'
            : 'draft';
    }

    /**
     * Clean filters before saving.
     */
    protected function cleanFilters(array $validated): array
    {
        if (($validated['audience_type'] ?? null) !== 'filtered') {
            return [];
        }

        $filters = $validated['filters'] ?? [];

        return collect($filters)
            ->filter(function ($value) {
                return $value !== null && $value !== '';
            })
            ->toArray();
    }

    /**
     * Clean Telegram buttons before saving.
     */
    protected function cleanButtons(array $validated): array
    {
        if (($validated['channel'] ?? null) !== 'telegram') {
            return [];
        }

        return collect($validated['buttons'] ?? [])
            ->map(function ($button) {
                return [
                    'text' => trim($button['text'] ?? ''),
                    'type' => $button['type'] ?? 'url',
                    'url' => trim($button['url'] ?? ''),
                ];
            })
            ->filter(function ($button) {
                return !empty($button['text'])
                    && !empty($button['url'])
                    && in_array(
                        $button['type'],
                        ['url', 'web_app'],
                        true
                    );
            })
            ->values()
            ->toArray();
    }

    /**
     * Normalize arrays before comparison.
     */
    protected function normalizeArray(array $value): array
    {
        return collect($value)
            ->mapWithKeys(function ($item, $key) {
                return [
                    (string) $key => is_array($item)
                        ? $this->normalizeArray($item)
                        : $item,
                ];
            })
            ->sortKeys()
            ->toArray();
    }
}