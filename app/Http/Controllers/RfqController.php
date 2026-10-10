<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\RfqRequest;
use App\Http\Requests\RfqUpdateRequest;
use App\Http\Resources\RfqResource;
use App\Models\Rfq;
use App\Models\RfqUpdate;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        $rfqs = Rfq::query()
            ->with('latestUpdate')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            // prioritas: kode persis (A1) atau satu grup huruf (A = A1..A5)
            ->when($request->filled('priority'), function ($q) use ($request) {
                $p = strtoupper($request->input('priority'));
                strlen($p) === 1 ? $q->where('priority_code', 'like', $p.'%') : $q->where('priority_code', $p);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($w) use ($s) {
                    foreach (['enquiry_no', 'opportunity_name', 'customer', 'area', 'type', 'quote_no', 'po_number', 'current_pic'] as $col) {
                        $w->orWhere($col, 'like', "%{$s}%");
                    }
                });
            })
            ->orderByDesc('rfq_date')
            ->orderByDesc('enquiry_no')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Data RFQ Ditemukan',
            'data' => RfqResource::collection($rfqs),
            'meta' => [
                'current_page' => $rfqs->currentPage(),
                'last_page' => $rfqs->lastPage(),
                'per_page' => $rfqs->perPage(),
                'total' => $rfqs->total(),
            ],
            'summary' => $this->summary(),
        ]);
    }

    // pilihan untuk form & filter (satu sumber: Rfq::PRIORITIES, bukan disalin di frontend)
    public function options()
    {
        $distinct = fn (string $col) => Rfq::query()->whereNotNull($col)->where($col, '!=', '')->distinct()->orderBy($col)->pluck($col);

        return response()->json([
            'status' => 'success',
            'data' => [
                'priorities' => collect(Rfq::PRIORITIES)->map(fn ($v, $code) => ['code' => $code, ...$v])->values(),
                'statuses' => Rfq::STATUSES,
                'types' => $distinct('type'),
                'sources' => $distinct('source'),
                'areas' => $distinct('area'),
                'customers' => $distinct('customer'),
                'pics' => $distinct('current_pic'),
            ],
        ]);
    }

    public function show(Rfq $rfq)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data RFQ Ditemukan',
            'data' => new RfqResource($rfq->load('updates.user:id,name')),
        ]);
    }

    public function store(RfqRequest $request)
    {
        $data = $request->validated();

        $rfq = $this->createWithEnquiryNo($data);

        $this->logActivity('Menambah RFQ', "RFQ {$rfq->enquiry_no} ({$rfq->customer}) berhasil ditambahkan");

        return response()->json([
            'status' => 'success',
            'message' => 'RFQ Berhasil Ditambahkan',
            'data' => new RfqResource($rfq),
        ], 201);
    }

    public function update(RfqRequest $request, Rfq $rfq)
    {
        $data = $request->validated();

        // nomor enquiry dikosongkan saat edit = tetap pakai yang lama
        if (empty($data['enquiry_no'])) {
            unset($data['enquiry_no']);
        }

        $rfq->update($data);

        $this->logActivity('Mengubah RFQ', "RFQ {$rfq->enquiry_no} ({$rfq->customer}) berhasil diubah");

        return response()->json([
            'status' => 'success',
            'message' => 'RFQ Berhasil Diubah',
            'data' => new RfqResource($rfq->fresh()),
        ]);
    }

    public function destroy(Rfq $rfq)
    {
        $label = "{$rfq->enquiry_no} ({$rfq->customer})";
        $rfq->delete();

        $this->logActivity('Menghapus RFQ', "RFQ {$label} berhasil dihapus");

        return response()->json([
            'status' => 'success',
            'message' => 'RFQ Berhasil Dihapus',
        ]);
    }

    // catatan progres bertanggal (+ opsional ganti prioritas)
    public function addUpdate(RfqUpdateRequest $request, Rfq $rfq)
    {
        $data = $request->validated();

        DB::transaction(function () use ($rfq, $data) {
            $rfq->updates()->create([
                'update_date' => $data['update_date'],
                'note' => $data['note'],
                'priority_code' => $data['priority_code'] ?? $rfq->priority_code,
                'user_id' => Auth::id(),
            ]);

            if (! empty($data['priority_code']) && $data['priority_code'] !== $rfq->priority_code) {
                $rfq->update(['priority_code' => $data['priority_code']]);
            }
        });

        $this->logActivity('Update RFQ', "Progres RFQ {$rfq->enquiry_no} ditambahkan");

        return response()->json([
            'status' => 'success',
            'message' => 'Progres RFQ Berhasil Ditambahkan',
            'data' => new RfqResource($rfq->fresh()->load('updates.user:id,name')),
        ], 201);
    }

    public function destroyUpdate(RfqUpdate $rfqUpdate)
    {
        $rfqUpdate->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Progres RFQ Berhasil Dihapus',
        ]);
    }

    /** Hitungan per grup prioritas (A–E) dan per status, masing-masing satu query. */
    private function summary(): array
    {
        $byGroup = Rfq::query()
            ->select(DB::raw('substr(priority_code, 1, 1) as grp'), DB::raw('count(*) as total'))
            ->groupBy('grp')
            ->pluck('total', 'grp');

        $byStatus = Rfq::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'by_priority_group' => collect(['A', 'B', 'C', 'D', 'E'])->mapWithKeys(fn ($g) => [$g => (int) ($byGroup[$g] ?? 0)]),
            'by_status' => collect(Rfq::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($byStatus[$s] ?? 0)]),
        ];
    }

    /** Buat RFQ; nomor enquiry otomatis kalau kosong. Ulangi sebentar bila dua user membuat di tanggal yang sama. */
    private function createWithEnquiryNo(array $data): Rfq
    {
        $auto = empty($data['enquiry_no']);

        for ($attempt = 0; ; $attempt++) {
            if ($auto) {
                $data['enquiry_no'] = Rfq::nextEnquiryNo($data['rfq_date']);
            }

            try {
                return Rfq::create($data);
            } catch (QueryException $e) {
                // 23000 = unique violation; hanya diulang untuk nomor otomatis
                if (! $auto || $attempt >= 4 || $e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
    }
}
