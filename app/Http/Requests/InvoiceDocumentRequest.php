<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // pertahanan berlapis selain middleware role:admin di route
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(Invoice::DOCUMENT_TYPES)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }
}
