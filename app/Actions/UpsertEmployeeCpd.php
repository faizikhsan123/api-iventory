<?php

namespace App\Actions;

use App\Http\Requests\EmployeeCpdRequest;
use App\Models\EmployeeCpd;
use App\Models\Employes;

// Simpan (buat/ubah) satu baris CPD per karyawan. Hanya kolom yang dikirim yang disentuh.
class UpsertEmployeeCpd
{
    /** @param array<string, mixed> $input */
    public function handle(Employes $employe, array $input): EmployeeCpd
    {
        $values = array_intersect_key($input, array_flip(EmployeeCpdRequest::fields()));

        return EmployeeCpd::updateOrCreate(['employes_id' => $employe->id], $values);
    }
}
