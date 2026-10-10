<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOnHandExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $search;

    protected $category;

    public function __construct($search = null, $category = null)
    {
        $this->search = $search;
        $this->category = $category;
    }

    public function collection(): Collection
    {
        return Item::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nama Barang',
            'Kategori',
            'Stok Saat Ini',
            'Unit',
            'Nilai Stok',
        ];
    }

    public function map($item): array
    {
        $stock = (int) $item->current_stock;

        return [
            $item->name,
            strtoupper($item->category ?? '-'),
            $stock,
            $item->unit ?? '-',
            round($stock * (float) $item->avg_price, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
