<?php

namespace App\Http\Requests;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RfqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'update_date' => ['required', 'date'],
            'note' => ['required', 'string', 'max:1000'],
            // opsional: ganti prioritas sekaligus saat menambah catatan
            'priority_code' => ['nullable', Rule::in(array_keys(Rfq::PRIORITIES))],
        ];
    }
}
