<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project' => ['nullable', 'string', 'max:100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'reviewer_name' => ['required', 'string', 'max:100'],
            'reviewer_title' => ['nullable', 'string', 'max:100'],
            'review_date' => ['required', 'date'],

            // jumlah KRA: Safety 10, Production 11, Cost 3 (skor 1-5)
            'scores' => ['required', 'array'],
            'scores.safety' => ['required', 'array', 'size:10'],
            'scores.production' => ['required', 'array', 'size:11'],
            'scores.cost' => ['required', 'array', 'size:3'],
            'scores.*.*' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
