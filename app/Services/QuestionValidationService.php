<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;

class QuestionValidationService
{
    /**
     * Cached category IDs.
     *
     * Example:
     * [
     *     '1' => 1,
     *     '2' => 2,
     * ]
     */
    protected array $categories = [];

    /**
     * Existing normalized English questions.
     *
     * Example:
     * [
     *     'what is the capital of ethiopia' => 125,
     * ]
     */
    protected array $existingQuestions = [];

    /**
     * Questions already encountered in the current import.
     *
     * Example:
     * [
     *     'what is the capital of ethiopia' => 7,
     * ]
     */
    protected array $fileQuestions = [];


    public function __construct()
    {
        /*
         * ---------------------------------------------------------
         * CACHE CATEGORY IDS
         * ---------------------------------------------------------
         *
         * We only need the IDs here because the Excel template
         * uses category_id.
         */

        $this->categories = Category::pluck('id')
            ->mapWithKeys(
                fn ($id) => [
                    (string) $id => $id,
                ]
            )
            ->toArray();


        /*
         * ---------------------------------------------------------
         * CACHE EXISTING DATABASE QUESTIONS
         * ---------------------------------------------------------
         *
         * We load them once rather than querying the database
         * for every Excel row.
         */

        $this->existingQuestions = Question::query()
            ->get(['id', 'question_text'])
            ->mapWithKeys(
                function ($question) {

                    $englishQuestion =
                        $question->getTranslation(
                            'question_text',
                            'en'
                        );

                    $normalized =
                        $this->normalizeQuestion(
                            $englishQuestion
                        );

                    if ($normalized === '') {
                        return [];
                    }

                    return [
                        $normalized =>
                            $question->id,
                    ];
                }
            )
            ->toArray();
    }


    /**
     * Validate a single import row.
     *
     * Returns:
     *
     * [
     *     'errors' => [],
     *     'warnings' => [],
     * ]
     */
    public function validate(
        array $data,
        int $rowNumber
    ): array {

        $errors = [];

        $warnings = [];


        /*
         * ---------------------------------------------------------
         * REQUIRED FIELDS
         * ---------------------------------------------------------
         */

        $required = [

            'category_id' =>
                'Category ID',

            'question_en' =>
                'English question',

            'option_a_en' =>
                'Option A English',

            'option_b_en' =>
                'Option B English',

            'option_c_en' =>
                'Option C English',

            'option_d_en' =>
                'Option D English',

            'correct_answer' =>
                'Correct answer',

            'difficulty' =>
                'Difficulty',
        ];


        foreach ($required as $field => $label) {

            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {

                $errors[] =
                    "{$label} is required.";
            }
        }


        /*
         * If basic required values are missing, return now.
         *
         * This prevents things like checking an empty
         * difficulty value and generating confusing secondary
         * validation messages.
         */
        if (!empty($errors)) {

            return [
                'errors' =>
                    array_values(
                        array_unique($errors)
                    ),

                'warnings' =>
                    [],
            ];
        }


        /*
         * ---------------------------------------------------------
         * CATEGORY
         * ---------------------------------------------------------
         */

        $categoryId =
            trim(
                (string) $data['category_id']
            );


        if (!ctype_digit($categoryId)) {

            $errors[] =
                'Category ID must be a valid number.';

        } elseif (
            !isset(
                $this->categories[$categoryId]
            )
        ) {

            $errors[] =
                "Category ID '{$categoryId}' does not exist.";
        }


        /*
         * ---------------------------------------------------------
         * QUESTION
         * ---------------------------------------------------------
         */

        $questionEn =
            trim(
                (string) $data['question_en']
            );


        $questionLength =
            mb_strlen($questionEn);


        /*
         * Minimum length.
         */

        if ($questionLength < 10) {

            $errors[] =
                'English question must be at least 10 characters.';
        }


        /*
         * Maximum length.
         */

        if ($questionLength > 500) {

            $errors[] =
                'English question cannot exceed 500 characters.';
        }


        /*
         * Amharic translation.
         *
         * This is a WARNING, not an error.
         */

        $questionAm =
            trim(
                (string) ($data['question_am'] ?? '')
            );


        if ($questionAm === '') {

            $warnings[] =
                'Amharic translation is missing for the question.';
        }


        /*
         * Amharic length check.
         */

        if (
            $questionAm !== '' &&
            mb_strlen($questionAm) > 500
        ) {

            $errors[] =
                'Amharic question cannot exceed 500 characters.';
        }


        /*
         * ---------------------------------------------------------
         * OPTIONS
         * ---------------------------------------------------------
         */

        $options = [

            'A' => [
                'en' =>
                    trim(
                        (string) $data['option_a_en']
                    ),

                'am' =>
                    trim(
                        (string) ($data['option_a_am'] ?? '')
                    ),
            ],

            'B' => [
                'en' =>
                    trim(
                        (string) $data['option_b_en']
                    ),

                'am' =>
                    trim(
                        (string) ($data['option_b_am'] ?? '')
                    ),
            ],

            'C' => [
                'en' =>
                    trim(
                        (string) $data['option_c_en']
                    ),

                'am' =>
                    trim(
                        (string) ($data['option_c_am'] ?? '')
                    ),
            ],

            'D' => [
                'en' =>
                    trim(
                        (string) $data['option_d_en']
                    ),

                'am' =>
                    trim(
                        (string) ($data['option_d_am'] ?? '')
                    ),
            ],
        ];


        /*
         * ---------------------------------------------------------
         * OPTION VALIDATION
         * ---------------------------------------------------------
         */

        $normalizedEnglishOptions = [];

        $normalizedAmharicOptions = [];


        foreach ($options as $letter => $option) {

            /*
             * -----------------------------------------------------
             * ENGLISH OPTION
             * -----------------------------------------------------
             */

            if ($option['en'] === '') {

                /*
                 * This is an ERROR because all four answer
                 * choices are required.
                 */
                $errors[] =
                    "Option {$letter} English text is missing.";

            } else {

                $length =
                    mb_strlen($option['en']);

                if ($length > 300) {

                    $errors[] =
                        "Option {$letter} English text cannot exceed 300 characters.";
                }

                $normalizedEnglishOptions[$letter] =
                    $this->normalizeText(
                        $option['en']
                    );
            }


            /*
             * -----------------------------------------------------
             * AMHARIC OPTION
             * -----------------------------------------------------
             */

            if ($option['am'] === '') {

                /*
                 * Warning only.
                 */
                $warnings[] =
                    "Option {$letter} Amharic translation is missing.";

            } else {

                if (
                    mb_strlen($option['am']) > 300
                ) {

                    $errors[] =
                        "Option {$letter} Amharic text cannot exceed 300 characters.";
                }

                $normalizedAmharicOptions[$letter] =
                    $this->normalizeText(
                        $option['am']
                    );
            }
        }


        /*
         * ---------------------------------------------------------
         * DUPLICATE ENGLISH OPTIONS
         * ---------------------------------------------------------
         */

        $this->detectDuplicateOptions(
            $normalizedEnglishOptions,
            'English',
            $errors
        );


        /*
         * ---------------------------------------------------------
         * DUPLICATE AMHARIC OPTIONS
         * ---------------------------------------------------------
         *
         * Only populated Amharic options are compared.
         */

        $this->detectDuplicateOptions(
            $normalizedAmharicOptions,
            'Amharic',
            $errors
        );


        /*
         * ---------------------------------------------------------
         * CORRECT ANSWER
         * ---------------------------------------------------------
         */

        $correctAnswer =
            strtolower(
                trim(
                    (string) $data['correct_answer']
                )
            );


        if (
            !in_array(
                $correctAnswer,
                ['a', 'b', 'c', 'd'],
                true
            )
        ) {

            $errors[] =
                'Correct answer must be A, B, C or D.';

        } else {

            /*
             * Check that the selected correct option
             * actually contains text.
             */

            $correctOption =
                strtoupper($correctAnswer);

            $correctText =
                $options[$correctOption]['en'] ?? '';

            if ($correctText === '') {

                $errors[] =
                    "Correct answer points to an empty option {$correctOption}.";
            }
        }


        /*
         * ---------------------------------------------------------
         * DIFFICULTY
         * ---------------------------------------------------------
         */

        $difficulty =
            strtolower(
                trim(
                    (string) $data['difficulty']
                )
            );


        if (
            !in_array(
                $difficulty,
                ['easy', 'medium', 'hard'],
                true
            )
        ) {

            $errors[] =
                'Difficulty must be easy, medium or hard.';
        }


        /*
         * ---------------------------------------------------------
         * ACTIVE STATUS
         * ---------------------------------------------------------
         */

        if (
            isset($data['is_active']) &&
            $data['is_active'] !== '' &&
            !$this->isValidBoolean(
                $data['is_active']
            )
        ) {

            $errors[] =
                'is_active must be 1, 0, true, false, yes or no.';
        }


        /*
         * ---------------------------------------------------------
         * DATABASE DUPLICATE
         * ---------------------------------------------------------
         */

        $normalizedQuestion =
            $this->normalizeQuestion(
                $questionEn
            );


        if (
            $normalizedQuestion !== '' &&
            isset(
                $this->existingQuestions[
                    $normalizedQuestion
                ]
            )
        ) {

            $existingQuestionId =
                $this->existingQuestions[
                    $normalizedQuestion
                ];

            $errors[] =
                "This question already exists in the database (Question #{$existingQuestionId}).";
        }


        /*
         * ---------------------------------------------------------
         * DUPLICATE IN CURRENT EXCEL FILE
         * ---------------------------------------------------------
         */

        if (
            $normalizedQuestion !== '' &&
            isset(
                $this->fileQuestions[
                    $normalizedQuestion
                ]
            )
        ) {

            $previousRow =
                $this->fileQuestions[
                    $normalizedQuestion
                ];

            $errors[] =
                "This question is duplicated in this import file (row {$previousRow}).";
        }


        /*
         * ---------------------------------------------------------
         * REGISTER CURRENT QUESTION
         * ---------------------------------------------------------
         *
         * Register it regardless of whether the row has other
         * validation errors. This allows later rows to correctly
         * identify it as a duplicate.
         */

        if ($normalizedQuestion !== '') {

            if (
                !isset(
                    $this->fileQuestions[
                        $normalizedQuestion
                    ]
                )
            ) {

                $this->fileQuestions[
                    $normalizedQuestion
                ] = $rowNumber;
            }
        }


        /*
         * ---------------------------------------------------------
         * RETURN RESULTS
         * ---------------------------------------------------------
         */

        return [

            'errors' =>
                array_values(
                    array_unique($errors)
                ),

            'warnings' =>
                array_values(
                    array_unique($warnings)
                ),
        ];
    }


    /**
     * Detect duplicate option text.
     */
    protected function detectDuplicateOptions(
        array $options,
        string $language,
        array &$errors
    ): void {

        $seen = [];


        foreach ($options as $letter => $text) {

            if ($text === '') {
                continue;
            }


            if (isset($seen[$text])) {

                $previousLetter =
                    $seen[$text];

                $errors[] =
                    "Option {$letter} has the same {$language} text as option {$previousLetter}.";

            } else {

                $seen[$text] =
                    $letter;
            }
        }
    }


    /**
     * Normalize normal text.
     *
     * Used for comparing:
     *
     * "What is Ethiopia?"
     * "what is ethiopia?"
     * " What  is   Ethiopia? "
     *
     * as equivalent values.
     */
    public function normalizeText(
        ?string $text
    ): string {

        if ($text === null) {
            return '';
        }


        $text =
            trim($text);


        /*
         * Normalize whitespace.
         */
        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );


        /*
         * Unicode lowercase.
         */
        return mb_strtolower(
            trim($text),
            'UTF-8'
        );
    }


    /**
     * Normalize question text.
     *
     * Punctuation is ignored for duplicate detection.
     *
     * Example:
     *
     * "What is Ethiopia?"
     * "What is Ethiopia"
     *
     * are treated as the same question.
     */
    public function normalizeQuestion(
        ?string $text
    ): string {

        $text =
            $this->normalizeText($text);


        if ($text === '') {
            return '';
        }


        /*
         * Remove punctuation/symbols while preserving:
         *
         * - English letters
         * - Amharic letters
         * - numbers
         * - whitespace
         */
        $text =
            preg_replace(
                '/[^\p{L}\p{N}\s]/u',
                '',
                $text
            );


        /*
         * Normalize whitespace again.
         */
        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );


        return trim($text);
    }


    /**
     * Validate a boolean value.
     */
    public function isValidBoolean(
        mixed $value
    ): bool {

        return in_array(
            strtolower(
                trim(
                    (string) $value
                )
            ),
            [
                '1',
                '0',
                'true',
                'false',
                'yes',
                'no',
            ],
            true
        );
    }


    /**
     * Convert Excel boolean value to PHP bool.
     */
    public function parseBoolean(
        mixed $value
    ): bool {

        return in_array(
            strtolower(
                trim(
                    (string) $value
                )
            ),
            [
                '1',
                'true',
                'yes',
            ],
            true
        );
    }
}