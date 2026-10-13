<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\ContractRenewalRequest;
use App\Http\Requests\ResignRequest;
use App\Models\ContractRenewal;
use App\Models\Employes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    use LogsActivity;

    // daftar karyawan + ringkasan kontrak (perpanjangan, jumlah review, rata-rata skor)
    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $employes = Employes::query()
            ->with('user:id,name')
            ->withCount(['contractRenewals', 'performanceReviews'])
            ->withAvg('performanceReviews as average_score', 'overall_avg')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($w) use ($search) {
                    $w->where('id_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw('contract_end is null')
            ->orderBy('contract_end')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Contract Ditemukan',
            'data' => $employes->getCollection()->map(fn ($e) => $this->summary($e))->values(),
            'meta' => [
                'current_page' => $employes->currentPage(),
                'last_page' => $employes->lastPage(),
                'per_page' => $employes->perPage(),
                'total' => $employes->total(),
            ],
        ]);
    }

    // detail kontrak satu karyawan: ringkasan + riwayat perpanjangan
    public function show(Employes $employe)
    {
        $employe->load('user:id,name')
            ->loadCount(['contractRenewals', 'performanceReviews'])
            ->loadAvg('performanceReviews as average_score', 'overall_avg')
            ->loadAvg('performanceReviews as safety_score', 'safety_avg')
            ->loadAvg('performanceReviews as production_score', 'production_avg')
            ->loadAvg('performanceReviews as cost_score', 'cost_avg');

        return response()->json([
            'status' => 'success',
            'message' => 'Detail Contract Ditemukan',
            'data' => [
                ...$this->summary($employe),
                'safety_score' => $this->round($employe->safety_score),
                'production_score' => $this->round($employe->production_score),
                'cost_score' => $this->round($employe->cost_score),
                'renewals' => $employe->contractRenewals()->latest('id')->get(),
            ],
        ]);
    }

    // perpanjang kontrak: simpan riwayat + geser contract_end ke tanggal baru
    public function storeRenewal(ContractRenewalRequest $request, Employes $employe)
    {
        $data = $request->validated();

        DB::transaction(function () use ($employe, $data) {
            $employe->contractRenewals()->create([
                'previous_end' => $employe->contract_end,
                'new_end' => $data['new_end'],
                'note' => $data['note'] ?? null,
            ]);

            $employe->update(['contract_end' => $data['new_end']]);
        });

        $name = $employe->user->name ?? $employe->id;
        $this->logActivity('Perpanjang Kontrak', "Kontrak {$name} diperpanjang sampai {$data['new_end']}");

        return response()->json([
            'status' => 'success',
            'message' => 'Kontrak Berhasil Diperpanjang',
        ], 201);
    }

    // resign: tanggal boleh di masa depan (terjadwal), status baru berubah saat tanggalnya tiba
    public function resign(ResignRequest $request, Employes $employe)
    {
        if ($employe->status === 'inactive') {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan sudah nonaktif',
            ], 422);
        }

        $data = $request->validated();
        $date = Carbon::parse($data['resign_date'])->startOfDay();
        $effective = $date->lte(now()->startOfDay());

        DB::transaction(function () use ($employe, $data, $date, $effective) {
            $employe->update([
                'status' => $effective ? 'inactive' : $employe->status,
                'left_at' => $date->toDateString(),
                'resign_reason' => $data['reason'],
                'exit_type' => 'resigned',
            ]);
        });

        $name = $employe->user->name ?? $employe->id;
        $when = $date->format('Y-m-d');
        $this->logActivity(
            $effective ? 'Resign Karyawan' : 'Jadwalkan Resign Karyawan',
            "Karyawan {$name} ".($effective ? 'resign' : 'dijadwalkan resign')." pada {$when}. Alasan: {$data['reason']}"
        );

        return response()->json([
            'status' => 'success',
            'message' => $effective ? 'Resign Berhasil Dicatat' : 'Resign Berhasil Dijadwalkan',
            'data' => [
                'status' => $employe->status,
                'left_at' => $when,
                'resign_reason' => $employe->resign_reason,
                'exit_type' => $employe->exit_type,
                'scheduled' => ! $effective,
            ],
        ]);
    }

    // hapus perpanjangan; kalau itu perpanjangan terakhir, contract_end dikembalikan
    public function destroyRenewal(ContractRenewal $contractRenewal)
    {
        DB::transaction(function () use ($contractRenewal) {
            $employe = $contractRenewal->employes;
            $isLatest = ! $employe->contractRenewals()->where('id', '>', $contractRenewal->id)->exists();

            $contractRenewal->delete();

            if ($isLatest) {
                $employe->update(['contract_end' => $contractRenewal->previous_end]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Perpanjangan Berhasil Dihapus',
        ]);
    }

    private function round($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }

    private function summary(Employes $e): array
    {
        return [
            'id' => $e->id,
            'id_number' => $e->id_number,
            'name' => $e->user->name ?? '-',
            'division' => $e->division,
            'position' => $e->position,
            'status' => $e->status,
            'contract_start' => $e->contract_start?->format('Y-m-d'),
            'contract_end' => $e->contract_end?->format('Y-m-d'),
            'left_at' => $e->left_at?->format('Y-m-d'),
            'resign_reason' => $e->resign_reason,
            'exit_type' => $e->exit_type,
            'renewals_count' => (int) $e->contract_renewals_count,
            'reviews_count' => (int) $e->performance_reviews_count,
            'average_score' => $this->round($e->average_score),
        ];
    }
}
