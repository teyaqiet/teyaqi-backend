<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'attempt_id' => $this->id,
            'challenge_id' => $this->challenge_id,
            'user_id' => $this->user_id,
            'score' => $this->score,
            'metrics' => [
                'correct_answers' => $this->correct_answers,
                'wrong_answers' => $this->wrong_answers,
                'time_spent_seconds' => $this->time_spent,
            ],
            'status' => [
                'completed' => $this->completed,
                'passed' => $this->passed,
                'reward_claimed' => $this->reward_claimed,
            ],
            'timestamps' => [
                'started_at' => $this->started_at?->toIso8601String(),
                'finished_at' => $this->finished_at?->toIso8601String(),
            ]
        ];
    }
}