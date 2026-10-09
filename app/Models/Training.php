<?php

namespace App\Models;

use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    protected $fillable = [
        'id_training',
        'division_training',
        'name_training',
        'by',
        'date',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }
}