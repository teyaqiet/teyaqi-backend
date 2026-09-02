<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecipient extends Model
{
    protected $fillable = [
        'broadcast_id',
        'user_id',
        'status',
        'attempts',
        'sent_at',
        'delivered_at',
        'error_message',
        'response',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'response' => 'array',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
 * Atomically claim this recipient for sending.
 *
 * Returns true only when this worker successfully
 * changed the recipient from pending -> sending.
 */
public function claimForSending(): bool
{
    $updated = static::query()
        ->whereKey($this->id)
        ->where('status', 'pending')
        ->update([
            'status' => 'sending',
            'updated_at' => now(),
        ]);

    if ($updated !== 1) {
        $this->refresh();

        return false;
    }

    $this->refresh();

    return true;
}
/**
 * Mark this recipient as successfully sent.
 */
public function markAsSent(
    ?string $response = null
): void {
    $this->update([
        'status' => 'sent',
        'sent_at' => now(),
        'error_message' => null,
        'response' => $response,
        'updated_at' => now(),
    ]);
}

/**
 * Mark this recipient as failed.
 */
public function markAsFailed(
    string $message,
    ?string $response = null
): void {
    $this->update([
        'status' => 'failed',
        'error_message' => $message,
        'response' => $response,
        'updated_at' => now(),
    ]);
}
}