<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// dipakai untuk tambah & ubah invoice
class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'service_name' => ['required', 'string', 'max:200'],
            'service_date' => ['nullable', 'date'],
            'service_description' => ['nullable', 'string', 'max:5000'],
            'division' => ['required', Rule::in(Invoice::DIVISIONS)],
            'client' => ['nullable', 'string', 'max:150'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'status' => ['nullable', Rule::in(Invoice::STATUSES)],
            'invoice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
