<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
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
            'brand' => 'nullable|max:20',
            'type' => 'nullable|max:20|string',
            'min_stock' => 'nullable|numeric',
            'size' => 'nullable||string',
            'unit' => 'required|in:pcs,set,unit,pair,others',
            'price' => 'nullable|string',
            'description' => 'nullable|max:200'

           
        ];

       
    }
}
