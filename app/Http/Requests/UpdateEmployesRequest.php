<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Sesuaikan nama parameter route dengan routes/api.php.
        $employe = $this->route('employe');

        $employeId = is_object($employe)
            ? $employe->id
            : $employe;

        return [
            'id_number' => [
                'required',
                'string',
                'min:4',
                'max:20',
                Rule::unique('employes', 'id_number')
                    ->ignore($employeId),
            ],

            'name' => [
                'required',
                'string',
                'min:4',
                'max:50',
            ],

            'file' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,svg',
                'max:5120',
            ],

            'division' => [
                'required',
                'in:Gas Analyzer,I&C-PMR,I&C-ER',
            ],

            'position' => [
                'required',
                'in:Technician,Supervisor,Foreman,Safety',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],

            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'left_at' => ['nullable', 'date'],

            'ktp_address' => [
                'nullable',
                'string',
                'min:10',
                'max:200',
            ],

            'actual_address' => [
                'nullable',
                'string',
                'min:10',
                'max:200',
            ],

            'emergency_contact' => [
                'nullable',
                'string',
                'min:10',
                'max:20',
            ],
        ];
    }
}
