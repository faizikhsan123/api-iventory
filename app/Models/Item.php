<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /** Sumber tunggal nilai kategori barang (urutan = urutan enum DB). */
    public const CATEGORIES = ['apd', 'tools', 'atk', 'others'];

    protected $fillable = [
        'file',
        'category',
        'name',
        'brand',
        'type',
        'part_number',
        'unit',
        'current_stock',
        'status',
        'min_stock',
        'description',
        'price',
        'avg_price',
    ];

    public function transaction_items()
    {
        return $this->hasMany(TransactionItem::class, 'items_id');
    }

    public function stock_history()
    {
        return $this->hasMany(StockHistory::class);
    }

    public function price_histories()
    {
        return $this->hasMany(ItemPriceHistory::class);
    }
}
