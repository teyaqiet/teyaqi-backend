<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionImportService
{
    /**
     * Import validated questions.
     */
    public function import(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $imported = 0;

        DB::transaction(function () use ($rows, &$imported) {

            foreach ($rows as $row) {

                $difficulty = strtolower(trim($row['difficulty']));

                $difficultyScore = match ($difficulty) {
                    'easy' => 25,
                    'medium' => 50,
                    'hard' => 75,
                    default => 50,
                };

                Question::create([
                    'category_id' => $row['category_id'],

                    'question_text' => $row['question_text'],

                    'option_a' => $row['option_a'],
                    'option_b' => $row['option_b'],
                    'option_c' => $row['option_c'],
                    'option_d' => $row['option_d'],

                    'correct_answer' => $row['correct_answer'],

                    'difficulty' => $difficulty,
                    'difficulty_score' => $difficultyScore,

                    'explanation' => $row['explanation'],

                    'is_active' => $row['is_active'],
                ]);

                $imported++;
            }
        });

        return $imported;
    }
}