<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    /** @use HasFactory<\Database\Factories\StockHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'item_id',
        'supplier_id',
        'transactions_id',
        'qty',
        'type',
        'note',
        'user_id'
    ];

    public function item(){
        return $this->belongsTo(Item::class);
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function transaction(){
        return $this->belongsTo(Transaction::class);
    }
}
