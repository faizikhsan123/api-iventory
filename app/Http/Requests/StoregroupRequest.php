<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoregroupRequest extends FormRequest
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
            'name_group' => 'required|string|max:255',
           
            'employes_ids' => 'nullable|array', // ini artinya employes_ids bisa null atau array
            'employes_ids.*' => 'integer|exists:employes,id', // jika employes_ids tidak null, maka setiap elemen dalam array harus berupa integer dan ada di tabel employes
        ];
    }
}
