<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    protected $fillable = [
        'employes_id',
        'project',
        'period_start',
        'period_end',
        'reviewer_name',
        'reviewer_title',
        'review_date',
        'scores',
        'safety_avg',
        'production_avg',
        'cost_avg',
        'overall_avg',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'review_date' => 'date:Y-m-d',
        'scores' => 'array',
        'safety_avg' => 'float',
        'production_avg' => 'float',
        'cost_avg' => 'float',
        'overall_avg' => 'float',
    ];

    public function employes(): BelongsTo
    {
        return $this->belongsTo(Employes::class, 'employes_id');
    }
}
