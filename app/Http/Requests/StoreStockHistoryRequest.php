<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockHistoryRequest extends FormRequest
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
            'item_id' => 'required|exists:items,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            // 'transaction_id' => 'nullable|exists:transactions,id',
            'qty' => 'required|numeric|min:1|max:1000000',
            // 'type' => 'required|in:in,out',
           
            // 'user_id' => 'nullable|exists:users,id',
        ];
    }
}
