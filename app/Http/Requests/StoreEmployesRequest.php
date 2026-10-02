<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployesRequest extends FormRequest
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
            'file' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'name' => ['required', 'string', 'max:50', 'min:4'],
            // 'email' => ['required', 'email:dns,rfc', 'max:50', 'unique:users,email'],
            // 'password' => ['required', 'string', 'min:8', 'max:200'],
            'division' => ['required', 'in:Gas Analyzer,I&C-PMR,I&C-ER'],
            'position' => ['required', 'in:Technician,Supervisor,Foreman,Safety'],
        ];
    }
}
