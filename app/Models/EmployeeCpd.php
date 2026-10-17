<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCpd extends Model
{
    // Daftar pilihan tetap (sumber tunggal); kolom DB tetap string.
    public const GENDERS = ['Laki-laki', 'Perempuan'];

    public const RELIGIONS = ['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    public const MARITAL_STATUSES = ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'];

    public const EMERGENCY_RELATIONSHIPS = ['Suami', 'Istri', 'Ayah', 'Ibu', 'Anak', 'Saudara Kandung', 'Lainnya'];

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
