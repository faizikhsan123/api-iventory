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
            'file' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'name' => 'required|max:50',
            'category' => 'required|in:apd,tools',
            'brand' => 'required|max:20',
            'type' => 'nullable|max:20|string',
            'min_stock' => 'nullable|numeric',
            'size' => 'required|in:s,m,l,xl,xxl,universal',
            'unit' => 'required|in:pcs,set,unit,pair',
            'description' => 'nullable|max:200'

           
        ];

       
    }
}
