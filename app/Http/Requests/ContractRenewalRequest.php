<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // tanggal berakhir baru harus setelah tanggal berakhir kontrak saat ini
        $currentEnd = $this->route('employe')?->contract_end?->format('Y-m-d');

        return [
            'new_end' => ['required', 'date', $currentEnd ? "after:{$currentEnd}" : 'after:1970-01-01'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_end.after' => 'Tanggal berakhir baru harus setelah tanggal berakhir kontrak saat ini',
        ];
    }
}
