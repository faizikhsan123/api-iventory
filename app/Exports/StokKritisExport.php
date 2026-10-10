<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StokKritisExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection(): Collection
    {
        // kondisi sama persis kayak ItemController@lowStock
        return Item::query()
            ->whereRaw('current_stock < min_stock')
            ->orderBy('current_stock') // yang paling kritis di atas
            ->get();
    }

    public function headings(): array
    {
        return [
            // 'Part Number',
            'Barang',
            'Kategori',
            'Stok Saat Ini',
            'Min. Stok',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            // $item->part_number,
            $item->name,
            strtoupper($item->category),
            $item->current_stock.' '.$item->unit,
            $item->min_stock.' '.$item->unit,
            // logika sama kayak FE: <= 0 habis, selain itu menipis
            (int) $item->current_stock <= 0 ? 'Habis' : 'Menipis',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
