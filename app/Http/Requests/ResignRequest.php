<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // tanggal resign boleh di masa depan (terjadwal), tapi tidak sebelum kontrak mulai
        $start = $this->route('employe')?->contract_start?->format('Y-m-d');

        return [
            'resign_date' => ['required', 'date', $start ? "after_or_equal:{$start}" : 'after:1970-01-01'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'resign_date.after_or_equal' => 'Tanggal resign tidak boleh sebelum tanggal mulai kontrak',
        ];
    }
}
