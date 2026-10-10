<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCpd extends Model
{
    protected $table = 'employee_cpd';

    protected $fillable = [
        'employes_id',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'marital_status',
        'religion',
        'nik_ktp',
        'npwp',
        'bpjs_labour_no',
        'phone',
        'home_address',
        'province',
        'city',
        'post_code',
        'contract_number',
        'department',
        'employee_type',
        'ptfi_assigned_uid',
        'emergency_name',
        'emergency_phone',
        'emergency_relationship',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'nik_ktp' => 'encrypted',
        'npwp' => 'encrypted',
    ];

    public function employes(): BelongsTo
    {
        return $this->belongsTo(Employes::class, 'employes_id');
    }
}
