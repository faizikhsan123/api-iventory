<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity',
        'detail',
        'date',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
