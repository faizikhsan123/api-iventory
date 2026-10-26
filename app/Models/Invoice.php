<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    // urutan tahapan invoice jasa (ubah di sini + fe-iventory/src/lib/invoice.ts kalau tahapannya berbeda)
    public const STATUSES = [
        'drafting_timesheet',
        'waiting_approved_timesheet',
        'waiting_quote_or_invoicing',
        'waiting_service_receipt',
        'waiting_work_order',
        'waiting_payment',
        'paid',
    ];

    // divisi invoice sengaja tanpa Safety (beda dari Division::values() yang dipakai karyawan/training)
    public const DIVISIONS = ['Gas Analyzer', 'I&C-PMR', 'I&C-ER', 'Dryer'];

    protected $fillable = [
        'invoice_number',
        'service_name',
        'service_date',
        'service_description',
        'division',
        'client',
        'amount',
        'status',
        'invoice_date',
        'notes',
        'quote_document',
        'quote_document_name',
        'wo_document',
        'wo_document_name',
        'invoice_document',
        'invoice_document_name',
    ];

    // jenis dokumen pendukung (whitelist; juga dipakai di route constraint)
    public const DOCUMENT_TYPES = ['quote', 'wo', 'invoice'];

    public const DOCUMENT_DISK = 'local';

    // path storage & nama asli tidak ikut di JSON; yang keluar hanya `documents`
    protected $hidden = [
        'quote_document', 'quote_document_name',
        'wo_document', 'wo_document_name',
        'invoice_document', 'invoice_document_name',
    ];

    protected $appends = ['documents'];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'service_date' => 'date:Y-m-d',
        'amount' => 'float',
    ];

    /** @return array<string, array{name: ?string, has_file: bool}|null> */
    public function getDocumentsAttribute(): array
    {
        $out = [];
        foreach (self::DOCUMENT_TYPES as $type) {
            $path = $this->getAttribute("{$type}_document");
            $out[$type] = $path
                ? ['name' => $this->getAttribute("{$type}_document_name"), 'has_file' => true]
                : null;
        }

        return $out;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class)->orderBy('status_date')->orderBy('id');
    }
}
