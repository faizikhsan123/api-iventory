<?php

namespace App\Http\Requests;

use App\Enums\Division;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRequest extends FormRequest
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
            'id_training' => 'required|string|max:255',
            'division_training' => ['required', Rule::in(Division::values())],
            'name_training' => 'required|string|max:255',
            'by' => 'required|string|max:30',
            // 'date' => 'required|date_format:Y-m-d',

        ];
    }
}
