<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name_group',
      
    ];

    // satu grup bisa memiliki banyak employes
   public function employes()
    {
        return $this->hasMany(Employes::class, 'group_id');
    }
}
