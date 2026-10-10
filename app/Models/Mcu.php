<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Mcu extends Model
{
    protected $fillable = [
        'employes_id',
        'place_name',
        'mcu_name',
        'mcu_date',
        'document',
        'summary',
        'allergies',
        'next_mcu_date',
    ];

    protected $casts = [
        'mcu_date' => 'date:Y-m-d',
        'next_mcu_date' => 'date:Y-m-d',
    ];

    public function employes(): BelongsTo
    {
        return $this->belongsTo(Employes::class, 'employes_id');
    }

    /**
     * Tandai MCU terbaru tiap karyawan (atribut `is_latest`) dengan SATU query,
     * supaya resource tidak menjalankan query per baris (N+1).
     * Jadwal MCU berikutnya hanya dianggap aktif untuk MCU terbaru.
     *
     * @param  iterable<Mcu>  $mcus
     */
    public static function flagLatest(iterable $mcus): void
    {
        $mcus = Collection::make($mcus);

        if ($mcus->isEmpty()) {
            return;
        }

        $latestIds = static::query()
            ->whereIn('employes_id', $mcus->pluck('employes_id')->unique())
            ->orderByDesc('mcu_date')
            ->orderByDesc('id')
            ->get(['id', 'employes_id'])
            ->unique('employes_id')
            ->pluck('id')
            ->flip();

        foreach ($mcus as $mcu) {
            $mcu->setAttribute('is_latest', $latestIds->has($mcu->id));
        }
    }
}
