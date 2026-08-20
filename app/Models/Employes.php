<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employes extends Model
{
    /** @use HasFactory<\Database\Factories\EmployesFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'division',
        'position',
        'status',
    ];

    // satu karyawan sattu user
    public function user(){
        return $this->belongsTo(User::class);
    }

    // satu karyawan dapat memiliki banyak transaksi
    public function transactions(){
        return $this->hasMany(Transaction::class);
    }
}
