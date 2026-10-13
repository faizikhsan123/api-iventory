<?php

namespace App\Exports;

use App\Models\Rfq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RfqExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    // query sudah terfilter (sama dengan RfqController@index) dan terurut
    public function __construct(private Builder $query) {}

    public function collection(): Collection
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'No. Enquiry', 'Tanggal RFQ', 'Tipe', 'Sumber', 'Area', 'Nama Peluang', 'Deskripsi',
            'Ref. Customer', 'No. Quote', 'Customer', 'Kontak', 'Telepon Kontak', 'Supplier',
            'Quote Supplier', 'Brosur', 'Gambar', 'Status', 'Kode Prioritas', 'Panduan Prioritas',
            'PIC Prioritas', 'PO Diterima', 'No. PO', 'PIC Saat Ini', 'Rencana Aksi', 'Deadline',
            'Nilai', 'Tgl Update Terakhir', 'Update Terakhir',
        ];
    }

    public function map($rfq): array
    {
        $priority = Rfq::PRIORITIES[$rfq->priority_code] ?? null;
        $yn = fn ($v) => $v ? 'Ya' : 'Tidak';

        return [
            $rfq->enquiry_no,
            $rfq->rfq_date?->format('Y-m-d'),
            $rfq->type,
            $rfq->source,
            $rfq->area,
            $rfq->opportunity_name,
            $rfq->description,
            $rfq->customer_ref,
            $rfq->quote_no,
            $rfq->customer,
            $rfq->contact_name,
            $rfq->contact_phone,
            $rfq->supplier,
            $yn($rfq->has_supplier_quote),
            $yn($rfq->has_brochure),
            $yn($rfq->has_drawing),
            $rfq->status,
            $rfq->priority_code,
            $priority['guide'] ?? null,
            $priority['pic'] ?? null,
            $yn($rfq->po_received),
            $rfq->po_number,
            $rfq->current_pic,
            $rfq->action_plan,
            $rfq->deadline?->format('Y-m-d'),
            $rfq->amount,
            $rfq->latestUpdate?->update_date?->format('Y-m-d'),
            $rfq->latestUpdate?->note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
