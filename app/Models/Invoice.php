<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    // urutan tahapan invoice jasa (ubah di sini + fe-iventory/src/lib/invoice.ts kalau tahapannya berbeda)
    public const STATUSES = ['draft', 'submitted', 'verified', 'paid'];

    public const DIVISIONS = ['PMR', 'ER', 'Gas', 'Dryer'];

    protected $fillable = [
        'invoice_number',
        'title',
        'division',
        'client',
        'amount',
        'status',
        'invoice_date',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'amount' => 'float',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class)->orderBy('status_date')->orderBy('id');
    }
}
