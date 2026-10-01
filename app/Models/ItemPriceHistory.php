<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPriceHistory extends Model
{
    protected $fillable = ['item_id', 'user_id', 'old_price', 'new_price'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}