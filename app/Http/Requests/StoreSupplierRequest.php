<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'name' => 'required|string|min:4|max:50',
            'phone' => 'nullable|string|min:8|max:15',
            'email' => 'nullable|email:rfc,dns|unique:suppliers,email',
            'address' => 'nullable|string|min:3|max:200',
        ];
    }
}
