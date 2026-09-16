<?php

namespace App\Models;

use Database\Factories\EmployesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employes extends Model
{
    /** @use HasFactory<EmployesFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transactions_id',
        'division',
        'position',
        'status',
    ];

    // satu karyawan sattu user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // satu karyawan dapat memiliki banyak transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // ambil TransactionItem tapi lewat Transaction dulu (employes gak nyambung langsung ke transaction_items)
    public function transactionItems()
    {
        return $this->hasManyThrough(
            TransactionItem::class,  // tujuan akhir
            Transaction::class,      // tabel jembatan
            'employes_id',           // FK di transactions -> nunjuk employes
            'transactions_id',       // FK di transaction_items -> nunjuk transactions
            'id',                    // PK employes
            'id'                     // PK transactions
        );
    }
}
        