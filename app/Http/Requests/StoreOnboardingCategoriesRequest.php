<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnboardingCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:5'],
            'category_ids.*' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.min' => 'You must select at least 5 categories to start your daily journey.',
            'category_ids.*.exists' => 'One or more selected categories are invalid.',
        ];
    }
}