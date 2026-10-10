<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqUpdate extends Model
{
    protected $fillable = [
        'rfq_id',
        'update_date',
        'note',
        'priority_code',
        'user_id',
    ];

    protected $casts = [
        'update_date' => 'date:Y-m-d',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
