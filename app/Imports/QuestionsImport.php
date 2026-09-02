<?php

namespace App\Imports;

use App\Services\QuestionValidationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    protected array $errors = [];

    protected array $validRows = [];

    protected QuestionValidationService $validator;

    public function __construct()
    {
        $this->validator = app(
            QuestionValidationService::class
        );
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {

            /*
             * Excel row number.
             *
             * Row 1 contains the headings.
             */
            $rowNumber = $index + 2;

            $data = $row->toArray();

            /*
             * Ignore completely empty rows.
             */
            if ($this->isEmptyRow($data)) {
                continue;
            }

            /*
             * Run advanced validation.
             *
             * Result:
             *
             * [
             *     'errors' => [...],
             *     'warnings' => [...]
             * ]
             */
            $validation = $this->validator->validate(
                $data,
                $rowNumber
            );

            $errors = $validation['errors'] ?? [];
            $warnings = $validation['warnings'] ?? [];

            /*
             * Errors mean the question cannot be imported.
             */
            if (!empty($errors)) {

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'data' => $data,
                ];

                continue;
            }

            /*
             * Normalize values.
             */
            $categoryId = (int) $data['category_id'];

            $difficulty = strtolower(
                trim((string) $data['difficulty'])
            );

            $this->validRows[] = [

                'row' => $rowNumber,

                'category_id' => $categoryId,

                'question_text' => [

                    'en' => trim(
                        (string) $data['question_en']
                    ),

                    'am' => trim(
                        (string) ($data['question_am'] ?? '')
                    ),
                ],

                'option_a' => [

                    'en' => trim(
                        (string) $data['option_a_en']
                    ),

                    'am' => trim(
                        (string) ($data['option_a_am'] ?? '')
                    ),
                ],

                'option_b' => [

                    'en' => trim(
                        (string) $data['option_b_en']
                    ),

                    'am' => trim(
                        (string) ($data['option_b_am'] ?? '')
                    ),
                ],

                'option_c' => [

                    'en' => trim(
                        (string) $data['option_c_en']
                    ),

                    'am' => trim(
                        (string) ($data['option_c_am'] ?? '')
                    ),
                ],

                'option_d' => [

                    'en' => trim(
                        (string) $data['option_d_en']
                    ),

                    'am' => trim(
                        (string) ($data['option_d_am'] ?? '')
                    ),
                ],

                'correct_answer' => strtolower(
                    trim(
                        (string) $data['correct_answer']
                    )
                ),

                'difficulty' => $difficulty,

                'explanation' => [

                    'en' => trim(
                        (string) ($data['explanation_en'] ?? '')
                    ),

                    'am' => trim(
                        (string) ($data['explanation_am'] ?? '')
                    ),
                ],

                'is_active' =>
                    $this->validator->parseBoolean(
                        $data['is_active'] ?? true
                    ),

                /*
                 * Keep warnings so the preview can display them.
                 */
                'warnings' => $warnings,
            ];
        }
    }

    /**
     * Determine whether an Excel row is completely empty.
     */
    protected function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {

            if (
                $value !== null &&
                trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTotalRows(): int
    {
        return count($this->validRows)
            + count($this->errors);
    }
}