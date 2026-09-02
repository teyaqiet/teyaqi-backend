<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_spent'              => 'required|numeric',
            'max_streak'              => 'required|integer',
            'remaining_time_seconds'  => 'nullable|numeric',
            'remaining_time'          => 'nullable|numeric',
            'total_xp_earned'         => 'nullable|integer',
            'total_coins_earned'      => 'nullable|integer',
            'correct_answers'         => 'nullable|integer',
            'wrong_answers'           => 'nullable|integer',
            'score'                   => 'nullable|integer',
            
            // Accepts both standard 'responses' or alternative 'savedResponses' structural layouts safely
            'responses'               => 'required_without:savedResponses|array',
            'savedResponses'          => 'required_without:responses|array',
            
            // Dynamic validation wildcard structures processing
            'responses.*.question_id'     => 'required|integer',
            'responses.*.selected_option' => 'required|string',
            'responses.*.is_correct'      => 'required|boolean',
            'responses.*.remaining_time'  => 'nullable|numeric',
            
            'savedResponses.*.question_id'     => 'required|integer',
            'savedResponses.*.selected_option' => 'required|string',
            'savedResponses.*.is_correct'      => 'required|boolean',
            'savedResponses.*.remaining_time'  => 'nullable|numeric',
        ];
    }

    /**
     * Normalize payload mapping configuration layout before releasing array data.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        
        // Unify alternate frontend payload arrays seamlessly
        if (isset($data['savedResponses']) && !isset($data['responses'])) {
            $data['responses'] = $data['savedResponses'];
        }
        
        // Normalize custom interior data parameters keys inside response list items
        if (isset($data['responses'])) {
            $data['responses'] = array_map(function ($resp) {
                return [
                    'question_id'     => $resp['question_id'] ?? $resp['question_id'] ?? null,
                    'selected_option' => $resp['selected_option'] ?? $resp['selected_option'] ?? null,
                    'is_correct'      => (bool) ($resp['is_correct'] ?? false),
                    'remaining_time'  => $resp['remaining_time'] ?? null
                ];
            }, $data['responses']);
        }
        
        return $data;
    }
}