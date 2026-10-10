<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employes_ids' => 'required|array|min:1',
            'employes_ids.*' => 'integer|exists:employes,id',
            'date' => 'required|date_format:Y-m-d',
        ];
    }
}
