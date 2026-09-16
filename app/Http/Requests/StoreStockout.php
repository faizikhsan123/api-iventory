<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employes_id' => 'required|exists:employes,id',
            'date' => 'required|date_format:Y-m-d',
            'note' => 'nullable|string|max:200',
            'items' => 'required|array|min:1',
            'items.*.items_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|numeric|min:1|max:100',
        ];
    }
}