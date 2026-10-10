<?php

namespace App\Http\Controllers;

use App\Actions\UpsertEmployeeCpd;
use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\EmployeeCpdRequest;
use App\Http\Resources\EmployeeCpdResource;
use App\Models\Employes;

class EmployeeCpdController extends Controller
{
    use LogsActivity;

    // semua user login boleh lihat; NIK/NPWP disembunyikan resource untuk non-admin
    public function show(Employes $employe)
    {
        $cpd = $employe->cpd;

        return response()->json([
            'status' => 'success',
            'message' => 'Data CPD Ditemukan',
            'data' => $cpd ? new EmployeeCpdResource($cpd) : null,
        ]);
    }

    // khusus admin (middleware role:admin di routes)
    public function upsert(EmployeeCpdRequest $request, Employes $employe, UpsertEmployeeCpd $action)
    {
        $cpd = $action->handle($employe, $request->validated());

        $name = $employe->user?->name ?? $employe->id;
        // jangan mencatat nilai NIK/NPWP, hanya nama kolom yang diubah
        $this->logActivity('Mengubah Data CPD', "Data CPD karyawan {$name} diperbarui (".implode(', ', array_keys($request->validated())).')');

        return response()->json([
            'status' => 'success',
            'message' => 'Data CPD Berhasil Disimpan',
            'data' => new EmployeeCpdResource($cpd),
        ]);
    }
}
