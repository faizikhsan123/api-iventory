<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'employes_id',
        'note'
    ];

    public function employes()
    {
        return $this->belongsTo(Employes::class);
    }

    public function transaction_items()
    {
        return $this->hasMany(TransactionItem::class, 'transactions_id');
    }
}
