<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'pic',
        'spesialis',
        'phone',
        'email',
        'address',
        'status',
    ];

    public function stock_history()
    {
        return $this->hasMany(StockHistory::class);
    }
}
