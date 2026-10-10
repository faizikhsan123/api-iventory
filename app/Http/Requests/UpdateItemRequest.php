<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
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
            'file' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'name' => 'required|max:50',
            'category' => 'required|in:apd,tools,others',
            'brand' => 'nullable|max:50|string',
            'type' => 'nullable|max:50|string',
            'min_stock' => 'nullable|numeric',
            'part_number' => 'nullable|string|max:50',
            //    'size' => 'nullable|string',
            'unit' => 'required|in:pcs,set,unit,pair,others',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|max:200',

        ];
    }
}
