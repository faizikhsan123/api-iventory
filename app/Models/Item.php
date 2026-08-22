<?php

namespace App\Models;

use App\Models\TransactionItem ;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number',
        'file',
        'category',
        'name',
        'brand',
        'type',
        'size',
        'unit',
        'current_stock',
        'status',
        'min_stock',
        'description',
    ];

    public function transaction_items()
    {
        return $this->hasMany(TransactionItem::class, 'items_id');
    }

    public function stock_history(){
        return $this->hasMany(StockHistory::class);
    }
}
