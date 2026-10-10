<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\InvoiceStatusRequest;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 10), 100));

        // urut berdasarkan tahapan (whitelist asc|desc); selain itu abaikan
        $sortStatus = in_array($request->input('sort_status'), ['asc', 'desc'], true) ? $request->input('sort_status') : null;

        $invoices = Invoice::query()
            ->when($request->filled('division'), fn ($q) => $q->where('division', $request->input('division')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($w) use ($search) {
                    $w->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('service_name', 'like', "%{$search}%")
                        ->orWhere('client', 'like', "%{$search}%");
                });
            })
            ->when($sortStatus, function ($q) use ($sortStatus) {
                $placeholders = implode(',', array_fill(0, count(Invoice::STATUSES), '?'));
                $q->orderByRaw("FIELD(status, {$placeholders}) {$sortStatus}", Invoice::STATUSES);
            })
            ->latest('id')
            ->paginate($perPage);

        // ringkasan per status (ikut filter divisi) dan per divisi — masing-masing satu query
        $statusCounts = Invoice::query()
            ->when($request->filled('division'), fn ($q) => $q->where('division', $request->input('division')))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $divisionCounts = Invoice::query()
            ->select('division', DB::raw('count(*) as total'))
            ->groupBy('division')
            ->pluck('total', 'division');

        return response()->json([
            'status' => 'success',
            'message' => 'Data Invoice Ditemukan',
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'summary' => [
                'by_status' => collect(Invoice::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($statusCounts[$s] ?? 0)]),
                'by_division' => collect(Invoice::DIVISIONS)->mapWithKeys(fn ($d) => [$d => (int) ($divisionCounts[$d] ?? 0)]),
            ],
        ]);
    }

    public function show(Invoice $invoice)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data Invoice Ditemukan',
            'data' => $this->withLogs($invoice),
        ]);
    }

    public function store(InvoiceRequest $request)
    {
        $data = $request->validated();
        $status = $data['status'] ?? 'drafting_timesheet';

        $invoice = DB::transaction(function () use ($data, $status) {
            $invoice = Invoice::create([...$data, 'status' => $status]);

            $invoice->logs()->create([
                'status' => $status,
                'status_date' => $data['invoice_date'] ?? now()->toDateString(),
                'note' => 'Invoice dibuat',
                'user_id' => Auth::id(),
            ]);

            return $invoice;
        });

        $this->logActivity('Menambah Invoice', "Invoice {$invoice->service_name} ({$invoice->division}) berhasil ditambahkan");

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice Berhasil Ditambahkan',
            'data' => $invoice,
        ], 201);
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        // status diubah lewat endpoint khusus supaya tercatat di riwayat
        $invoice->update(collect($request->validated())->except('status')->all());

        $this->logActivity('Mengubah Invoice', "Invoice {$invoice->service_name} ({$invoice->division}) berhasil diubah");

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice Berhasil Diubah',
            'data' => $invoice->fresh(),
        ]);
    }

    // pindah tahapan + catat riwayatnya
    public function updateStatus(InvoiceStatusRequest $request, Invoice $invoice)
    {
        $data = $request->validated();

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update(['status' => $data['status']]);

            $invoice->logs()->create([
                'status' => $data['status'],
                'status_date' => $data['status_date'],
                'note' => $data['note'] ?? null,
                'user_id' => Auth::id(),
            ]);
        });

        $this->logActivity('Update Status Invoice', "Invoice {$invoice->service_name} ({$invoice->division}) menjadi {$data['status']}");

        return response()->json([
            'status' => 'success',
            'message' => 'Status Invoice Berhasil Diubah',
            'data' => $this->withLogs($invoice->fresh()),
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        $label = "{$invoice->service_name} ({$invoice->division})";
        $invoice->delete();

        $this->logActivity('Menghapus Invoice', "Invoice {$label} berhasil dihapus");

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice Berhasil Dihapus',
        ]);
    }

    private function withLogs(Invoice $invoice): array
    {
        return [...$invoice->toArray(), 'logs' => $invoice->logs()->with('user:id,name')->get()];
    }
}
