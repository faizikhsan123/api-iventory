<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// koreksi qty barang keluar / masuk: HANYA quantity
class UpdateQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
