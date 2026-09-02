<?php

namespace App\Services;

use App\Events\Player\XpMilestoneReached;
use App\Models\User;

class XpMilestoneService
{
    /**
     * XP milestones supported by the automation system.
     */
    protected array $milestones = [
        500,
        1000,
        2500,
        5000,
        10000,
        25000,
        50000,
        100000,
    ];

    /**
     * Check whether the XP change crossed a milestone.
     *
     * Only the highest crossed milestone is dispatched.
     */
    public function check(
        User $user,
        int $previousXp,
        int $newXp
    ): void {
        if ($newXp <= $previousXp) {
            return;
        }

        $milestone = collect($this->milestones)
            ->filter(
                fn (int $value) =>
                    $value > $previousXp &&
                    $value <= $newXp
            )
            ->max();

        if (!$milestone) {
            return;
        }

        event(
            new XpMilestoneReached(
                $user,
                $previousXp,
                $newXp,
                $milestone
            )
        );
    }
}