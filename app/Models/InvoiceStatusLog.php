<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceStatusLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'status',
        'status_date',
        'note',
        'user_id',
    ];

    protected $casts = [
        'status_date' => 'date:Y-m-d',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
