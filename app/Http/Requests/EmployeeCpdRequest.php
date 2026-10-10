<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// PUT /employes/{id}/cpd. Aturan juga dipakai bersarang (prefix "cpd.") oleh request karyawan.
class EmployeeCpdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(string $prefix = ''): array
    {
        // dipanggil framework tanpa argumen; prefix hanya untuk pemakaian statis
        return self::build($prefix);
    }

    public static function build(string $prefix = ''): array
    {
        $rules = [
            'date_of_birth' => ['nullable', 'date'],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'religion' => ['nullable', 'string', 'max:30'],
            'nik_ktp' => ['nullable', 'string', 'max:32'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'bpjs_labour_no' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'home_address' => ['nullable', 'string', 'max:500'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'post_code' => ['nullable', 'string', 'max:10'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'employee_type' => ['nullable', 'string', 'max:50'],
            'ptfi_assigned_uid' => ['nullable', 'string', 'max:50'],
            'emergency_name' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'emergency_relationship' => ['nullable', 'string', 'max:50'],
        ];

        $out = [];
        foreach ($rules as $k => $v) {
            $out[$prefix.$k] = $v;
        }

        return $out;
    }

    /** @return list<string> */
    public static function fields(): array
    {
        return array_keys(self::build());
    }
}
