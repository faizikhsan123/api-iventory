<?php

namespace App\Http\Requests;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// dipakai untuk tambah & ubah RFQ
class RfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rfq = $this->route('rfq');

        return [
            // kosong = dibuat otomatis (RFQ{Ymd}-{nnn})
            'enquiry_no' => ['nullable', 'string', 'max:50', Rule::unique('rfqs', 'enquiry_no')->ignore($rfq?->id)],
            'rfq_date' => ['required', 'date'],
            'type' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'opportunity_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'customer_ref' => ['nullable', 'string', 'max:100'],
            'quote_no' => ['nullable', 'string', 'max:100'],
            'customer' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'has_supplier_quote' => ['boolean'],
            'has_brochure' => ['boolean'],
            'has_drawing' => ['boolean'],
            'status' => ['required', Rule::in(Rfq::STATUSES)],
            'priority_code' => ['required', Rule::in(array_keys(Rfq::PRIORITIES))],
            'po_received' => ['boolean'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'current_pic' => ['nullable', 'string', 'max:100'],
            'action_plan' => ['nullable', 'string', 'max:3000'],
            'deadline' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }
}
