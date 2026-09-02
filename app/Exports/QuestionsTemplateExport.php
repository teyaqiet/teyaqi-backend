<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuestionsTemplateExport implements FromArray, WithHeadings
{
    /**
     * Excel column headings.
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
     * Example row.
     */
    public function array(): array
    {
        return [
            [
                1,
                'What is the capital city of Ethiopia?',
                'የኢትዮጵያ ዋና ከተማ የት ናት?',
                'Addis Ababa',
                'አዲስ አበባ',
                'Dire Dawa',
                'ድሬዳዋ',
                'Bahir Dar',
                'ባህር ዳር',
                'Mekelle',
                'መቀሌ',
                'a',
                'easy',
                'Addis Ababa is the capital city of Ethiopia.',
                'አዲስ አበባ የኢትዮጵያ ዋና ከተማ ናት።',
                1,
            ],
        ];
    }
}