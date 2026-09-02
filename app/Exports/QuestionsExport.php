<?php

namespace App\Exports;

use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuestionsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected ?int $categoryId = null,
        protected ?string $difficulty = null,
        protected ?string $status = null,
        protected array $questionIds = [],
    ) {
    }

    /**
     * Build the export query.
     */
    public function query(): Builder
    {
        return Question::query()
            ->when(
                !empty($this->questionIds),
                fn (Builder $query) =>
                    $query->whereIn('id', $this->questionIds)
            )
            ->when(
                $this->categoryId,
                fn (Builder $query) =>
                    $query->where('category_id', $this->categoryId)
            )
            ->when(
                $this->difficulty,
                fn (Builder $query) =>
                    $query->where('difficulty', $this->difficulty)
            )
            ->when(
                $this->status,
                fn (Builder $query) =>
                    $query->where(
                        'is_active',
                        $this->status === 'active'
                    )
            )
            ->with('category')
            ->orderBy('id');
    }

    /**
     * Excel column headings.
     *
     * difficulty_score is intentionally NOT included.
     */
    public function headings(): array
    {
        return [
            'category_id',
            'question_en',
            'question_am',
            'option_a_en',
            'option_a_am',
            'option_b_en',
            'option_b_am',
            'option_c_en',
            'option_c_am',
            'option_d_en',
            'option_d_am',
            'correct_answer',
            'difficulty',
            'explanation_en',
            'explanation_am',
            'is_active',
        ];
    }

    /**
     * Map question to Excel row.
     */
    public function map($question): array
    {
        return [
            $question->category_id,

            $question->getTranslation(
                'question_text',
                'en'
            ),

            $question->getTranslation(
                'question_text',
                'am'
            ),

            $question->getTranslation(
                'option_a',
                'en'
            ),

            $question->getTranslation(
                'option_a',
                'am'
            ),

            $question->getTranslation(
                'option_b',
                'en'
            ),

            $question->getTranslation(
                'option_b',
                'am'
            ),

            $question->getTranslation(
                'option_c',
                'en'
            ),

            $question->getTranslation(
                'option_c',
                'am'
            ),

            $question->getTranslation(
                'option_d',
                'en'
            ),

            $question->getTranslation(
                'option_d',
                'am'
            ),

            strtoupper($question->correct_answer),

            strtolower($question->difficulty),

            $question->getTranslation(
                'explanation',
                'en'
            ),

            $question->getTranslation(
                'explanation',
                'am'
            ),

            $question->is_active ? 1 : 0,
        ];
    }
}