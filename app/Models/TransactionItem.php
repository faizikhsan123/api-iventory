<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transactions_id',
        'items_id',
        'qty',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'items_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transactions_id');
    }
}