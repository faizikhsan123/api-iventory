<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRenewal extends Model
{
    protected $fillable = [
        'employes_id',
        'previous_end',
        'new_end',
        'note',
    ];

    protected $casts = [
        'previous_end' => 'date:Y-m-d',
        'new_end' => 'date:Y-m-d',
    ];

    public function employes(): BelongsTo
    {
        return $this->belongsTo(Employes::class, 'employes_id');
    }
}
