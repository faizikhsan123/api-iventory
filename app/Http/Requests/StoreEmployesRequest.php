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
            'name' => ['required', 'string', 'max:20', "min:4"],
            'email' => ['required', 'email:dns,rfc', 'max:50', 'unique:users,email', "max:50"],
            'password' => ['required', 'string', 'min:8', 'max:50'],
            'division' => 'required|in:GA',
            'INC-PMR',
            'INC-ER',
            'position' => 'required|in:Technician',
            'Supervisor',
            'Foreman',
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
