<?php

namespace App\Events\Player;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerInactive implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysInactive,
        public int $previousStreak,
        public ?string $lastActivityAt = null,
    ) {
    }
}