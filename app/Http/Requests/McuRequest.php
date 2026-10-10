<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// dipakai untuk store & update
class McuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employes_id' => [$this->route('mcu') ? 'sometimes' : 'required', 'exists:employes,id'],
            'place_name' => ['required', 'string', 'max:150'],
            'mcu_name' => ['nullable', 'string', 'max:150'],
            'mcu_date' => ['required', 'date'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'next_mcu_date' => ['nullable', 'date', 'after_or_equal:mcu_date'],
        ];
    }
}
