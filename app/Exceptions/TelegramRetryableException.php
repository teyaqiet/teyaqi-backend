<?php

namespace App\Exceptions;

use RuntimeException;

class TelegramRetryableException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        int $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}