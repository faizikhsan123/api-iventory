<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'employes_id',
    ];

    // satu transaction hanya memiliki satu employes
    public function employes(){
        return $this->belongsTo(employes::class, 'employes_id');
    }
}
