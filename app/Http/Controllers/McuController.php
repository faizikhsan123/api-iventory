<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\McuRequest;
use App\Http\Resources\McuResource;
use App\Models\Mcu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class McuController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $mcus = Mcu::query()
            ->with('employes.user')
            ->when($request->filled('employes_id'), fn ($q) => $q->where('employes_id', $request->integer('employes_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($w) use ($search) {
                    $w->where('place_name', 'like', "%{$search}%")
                        ->orWhere('mcu_name', 'like', "%{$search}%")
                        ->orWhere('allergies', 'like', "%{$search}%")
                        ->orWhereHas('employes.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('mcu_date')
            ->latest('id')
            ->paginate($perPage);

        Mcu::flagLatest($mcus->items());

        return response()->json([
            'status' => 'success',
            'message' => 'Data MCU Ditemukan',
            'data' => McuResource::collection($mcus),
            'meta' => [
                'current_page' => $mcus->currentPage(),
                'last_page' => $mcus->lastPage(),
                'per_page' => $mcus->perPage(),
                'total' => $mcus->total(),
            ],
        ]);
    }

    public function show(Mcu $mcu)
    {
        return $this->respond('Data MCU Ditemukan', $mcu);
    }

    public function store(McuRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('mcu', 'public');
        }

        $mcu = Mcu::create($data);

        $this->logActivity('Menambah Data MCU', "Data MCU karyawan {$this->employeeName($mcu)} berhasil ditambahkan");

        return $this->respond('Data MCU Berhasil Ditambahkan', $mcu, 201);
    }

    public function update(McuRequest $request, Mcu $mcu)
    {
        $data = $request->validated();

        if ($request->hasFile('document')) {
            if ($mcu->document) {
                Storage::disk('public')->delete($mcu->document);
            }
            $data['document'] = $request->file('document')->store('mcu', 'public');
        } else {
            unset($data['document']); // dokumen lama dipertahankan
        }

        $mcu->update($data);

        $this->logActivity('Mengubah Data MCU', "Data MCU karyawan {$this->employeeName($mcu)} berhasil diubah");

        return $this->respond('Data MCU Berhasil Diubah', $mcu);
    }

    public function destroy(Mcu $mcu)
    {
        $name = $this->employeeName($mcu);

        if ($mcu->document) {
            Storage::disk('public')->delete($mcu->document);
        }

        $mcu->delete();

        $this->logActivity('Menghapus Data MCU', "Data MCU karyawan {$name} berhasil dihapus");

        return response()->json([
            'status' => 'success',
            'message' => 'Data MCU Berhasil Dihapus',
        ]);
    }

    private function respond(string $message, Mcu $mcu, int $status = 200)
    {
        $mcu->load('employes.user');
        Mcu::flagLatest([$mcu]);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => new McuResource($mcu),
        ], $status);
    }

    private function employeeName(Mcu $mcu): string
    {
        return (string) ($mcu->employes?->user?->name ?? $mcu->employes_id);
    }
}
