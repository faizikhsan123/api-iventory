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
        'waiting_service_receipt',
        'waiting_work_order',
        'paid',
    ];

    public const DIVISIONS = ['Gas Analyzer', 'I&C-PMR', 'I&C-ER', 'Dryer', 'Safety'];

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
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'service_date' => 'date:Y-m-d',
        'amount' => 'float',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class)->orderBy('status_date')->orderBy('id');
    }
}
