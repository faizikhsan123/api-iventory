<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_training' => 'sometimes|string|max:255',
            'division_training' => 'sometimes|in:Gas Analyzer,I&C-PMR,I&C-ER,Safety',
            'name_training' => 'sometimes|string|max:255',
            'created_by' => 'sometimes|string|max:30',
            // 'date' => 'sometimes|date_format:Y-m-d',

        ];
    }
}
