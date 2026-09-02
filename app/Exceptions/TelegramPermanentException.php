<?php

namespace App\Exceptions;

use RuntimeException;

class TelegramPermanentException extends RuntimeException
{
    public function isRetryable(): bool
    {
        return false;
    }
}