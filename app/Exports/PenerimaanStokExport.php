<?php

namespace App\Exports;

use App\Models\StockHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenerimaanStokExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return StockHistory::query()
            ->with(['item', 'supplier'])
            ->where('type', 'in') // cuma penerimaan stok
            ->when($this->start, fn ($q) => $q->whereDate('date', '>=', $this->start))
            ->when($this->end, fn ($q) => $q->whereDate('date', '<=', $this->end))
            ->latest('date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Barang',
            'Kategori',
            'Supplier',
            'Spesialis',
            'Qty',
            'Stock Akhir',
        ];
    }

    public function map($history): array
    {
        return [
            $history->date,
            $history->item->name ?? '-',
            strtoupper($history->item->category ?? '-'),
            $history->supplier->name ?? '-',
            $history->supplier->spesialis ?? '-',
            $history->qty.' '.($history->item->unit ?? ''),
            $history->item->current_stock ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
