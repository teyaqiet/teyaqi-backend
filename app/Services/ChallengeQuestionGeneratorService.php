<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Question;
use Illuminate\Support\Collection;

class ChallengeQuestionGeneratorService
{
    /**
     * Resolves and extracts questions for a specific Challenge session context.
     */
    public function generate(Challenge $challenge): Collection
    {
        // Strategy 1: Prioritize Manual Target Overrides
        if ($challenge->manualQuestions()->exists()) {
            return $challenge->manualQuestions()->get();
        }

        // Determine the absolute total target count for this overall challenge session
        $globalTargetCount = (int) ($challenge->question_count ?? 5);

        // Strategy 2: Execute Rule-Based Dynamic Gathering Flow
        $rules = $challenge->rules;
        if ($rules->isEmpty()) {
            return $this->getFallbackQuestions($challenge, [], $globalTargetCount);
        }

        $questionsPool = collect();

        foreach ($rules as $rule) {
            $query = Question::query();

            // Apply rule filters strictly
            if ($rule->category_id) {
                $query->where('category_id', $rule->category_id);
            }
            if ($rule->topic_id) {
                $query->where('topic_id', $rule->topic_id);
            }
            if ($rule->difficulty) {
                $query->where('difficulty', $rule->difficulty);
            }
            
            // Safe JSON tag parsing for comma-separated form inputs
            if (!empty($rule->tags)) {
                $tagsArray = is_string($rule->tags) 
                    ? array_map('trim', explode(',', $rule->tags)) 
                    : (array) $rule->tags;

                $query->where(function ($q) use ($tagsArray) {
                    foreach ($tagsArray as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            // Handle raw strings or Enum instances cleanly
            $selectionType = is_object($rule->selection_type) ? $rule->selection_type->value : $rule->selection_type;

            $strategyQuestions = match ($selectionType) {
                'weighted' => $query->orderBy('weight', 'desc'),
                default    => $query->inRandomOrder()
            };

            // 🌟 FIX: Pull based on the specific rule's count, fallback to the global cap if null
            $rulePullCount = (int) ($rule->question_count ?? $globalTargetCount);

            $fetched = $strategyQuestions->limit($rulePullCount)->get();
            $questionsPool = $questionsPool->merge($fetched);
        }

        // De-duplicate if rules pulled overlapping questions
        $questionsPool = $questionsPool->unique('id');

        // Pad with fallbacks if rules didn't yield enough items to satisfy the challenge baseline
        if ($questionsPool->count() < $globalTargetCount) {
            $extraNeeded = $globalTargetCount - $questionsPool->count();
            $fallback = $this->getFallbackQuestions($challenge, $questionsPool->pluck('id')->toArray(), $extraNeeded);
            $questionsPool = $questionsPool->merge($fallback);
        }

        // Force slice to match the exact expected structure count
        return $questionsPool->take($globalTargetCount);
    }

    /**
     * Fetch fallback generic questions matching global challenge parameters.
     */
    protected function getFallbackQuestions(Challenge $challenge, array $excludeIds = [], int $limit = null): Collection
    {
        $fallbackCap = $limit ?? (int) ($challenge->question_count ?? 5);

        return Question::query()
            ->when($challenge->category_id, fn($q) => $q->where('category_id', $challenge->category_id))
            ->when($challenge->difficulty, fn($q) => $q->where('difficulty', $challenge->difficulty))
            ->whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->limit($fallbackCap)
            ->get();
    }
}