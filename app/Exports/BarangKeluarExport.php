<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarangKeluarExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $start;

    protected $end;

    public function __construct($start = null, $end = null)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function collection(): Collection
    {
        return Transaction::query()
            ->with(['employes.user', 'transaction_items.item'])
            ->when($this->start, fn ($q) => $q->whereDate('date', '>=', $this->start))
            ->when($this->end, fn ($q) => $q->whereDate('date', '<=', $this->end))
            ->latest('date')
            ->get();
    }

    public function headings(): array
    {
        return [
            // 'No. Transaksi',
            'Tanggal',
            'Barang',
            'Karyawan',
            'Divisi',
            'Qty',
            'Stock Akhir',
            'Catatan',
        ];
    }

    public function map($trx): array
    {
        // gabungin nama barang jadi 1 string, sama kayak TransactionResource
        $barang = $trx->transaction_items
            ->map(fn ($ti) => $ti->item->name ?? null)
            ->filter()
            ->implode(', ');

        $totalQty = $trx->transaction_items->sum('qty');

        $totalStock = $trx->transaction_items
            ->sum(fn ($ti) => $ti->item->current_stock ?? 0);

        return [
            // $trx->transaction_number,
            Carbon::parse($trx->date)->translatedFormat('d F Y'),
            $barang ?: '-',
            $trx->employes->user->name ?? '-',
            $trx->employes->division ?? '-',
            $totalQty,
            $totalStock,
            $trx->note ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
