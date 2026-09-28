<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class recordPengeluaran implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $start;
    protected $end;

    public function __construct($start = null, $end = null)
    {
        // default bulan ini, sama kayak topBorrowed
        $this->start = $start ?? now()->startOfMonth()->toDateString();
        $this->end = $end ?? now()->endOfMonth()->toDateString();
    }

    public function collection(): Collection
    {
        return Item::query()
            ->withSum(['stock_history as total_pinjam' => function ($q) {
                $q->where('type', 'out')
                    ->whereDate('date', '>=', $this->start)
                    ->whereDate('date', '<=', $this->end);
            }], 'qty')
            ->having('total_pinjam', '>', 0)
            ->orderByDesc('total_pinjam')
            ->get()
            ->values()
            ->map(function ($item, $i) {
                $item->rank = $i + 1;

                return $item;   
            });
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'Nama Barang',
            'Kategori',
            'Stok Saat Ini',
            'Total Diberikan',
        ];
    }

    public function map($item): array
    {
        return [
            $item->rank,
            $item->name,
            strtoupper($item->category),
            $item->current_stock,
            (int) $item->total_pinjam,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}