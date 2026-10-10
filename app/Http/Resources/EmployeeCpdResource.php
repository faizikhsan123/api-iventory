<?php

namespace App\Http\Resources;

use App\Http\Requests\EmployeeCpdRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeCpdResource extends JsonResource
{
    // kolom sensitif: key tidak dikirim sama sekali untuk non-admin
    private const SENSITIVE = ['nik_ktp', 'npwp'];

    public function toArray(Request $request): array
    {
        $isAdmin = (bool) $request->user()?->hasRole('admin');

        $out = ['id' => $this->id, 'employes_id' => $this->employes_id];

        foreach (EmployeeCpdRequest::fields() as $field) {
            if (in_array($field, self::SENSITIVE, true) && ! $isAdmin) {
                continue;
            }
            $out[$field] = $field === 'date_of_birth'
                ? $this->date_of_birth?->format('Y-m-d')
                : $this->{$field};
        }

        return $out;
    }
}
