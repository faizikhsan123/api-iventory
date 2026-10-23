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
        // tanggal lampau boleh (rekap riwayat); hanya tidak boleh sebelum kontrak mulai
        $start = $this->route('employe')?->contract_start?->format('Y-m-d');

        return [
            'new_end' => ['required', 'date', ...($start ? ["after_or_equal:{$start}"] : [])],
            'previous_end' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_end.after_or_equal' => 'Tanggal berakhir baru tidak boleh sebelum tanggal mulai kontrak',
        ];
    }
}
