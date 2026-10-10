<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_number' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'unique:employes,id_number',
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

            'contract_start' => ['required', 'date'],
            'contract_end' => ['required', 'date', 'after_or_equal:contract_start'],

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
