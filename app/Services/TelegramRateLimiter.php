<?php

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;

class TelegramRateLimiter
{
    protected RateLimiter $limiter;

    protected int $maxAttempts;

    protected int $decaySeconds;

    protected string $key = 'telegram:broadcast';

    public function __construct(
        RateLimiter $limiter
    ) {
        $this->limiter = $limiter;

        $this->maxAttempts = max(
            1,
            (int) config(
                'services.telegram.broadcast_rate_limit',
                20
            )
        );

        $this->decaySeconds = max(
            1,
            (int) config(
                'services.telegram.broadcast_rate_window',
                1
            )
        );
    }

    /**
     * Determine whether another Telegram request
     * can be sent immediately.
     */
    public function available(): bool
    {
        return !$this->limiter->tooManyAttempts(
            $this->key,
            $this->maxAttempts
        );
    }

    /**
     * Reserve one Telegram request slot.
     */
    public function attempt(): bool
    {
        if (!$this->available()) {
            return false;
        }

        $this->limiter->hit(
            $this->key,
            $this->decaySeconds
        );

        return true;
    }

    /**
     * Wait until a Telegram request slot is available.
     */
    public function wait(): void
    {
        while (!$this->attempt()) {
            $seconds = $this->availableIn();

            if ($seconds > 0) {
                sleep($seconds);
            }
        }
    }

    /**
     * Return the number of seconds until another
     * request can be sent.
     */
    public function availableIn(): int
    {
        if ($this->available()) {
            return 0;
        }

        return max(
            1,
            $this->limiter->availableIn($this->key)
        );
    }

    /**
     * Return the configured maximum number
     * of requests per window.
     */
    public function limit(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Return the configured rate-limit window.
     */
    public function window(): int
    {
        return $this->decaySeconds;
    }

    /**
     * Return the limiter key.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Reset the current limiter.
     */
    public function clear(): void
    {
        $this->limiter->clear($this->key);

        Log::debug(
            'Telegram broadcast rate limiter cleared.',
            [
                'key' => $this->key,
            ]
        );
    }
}