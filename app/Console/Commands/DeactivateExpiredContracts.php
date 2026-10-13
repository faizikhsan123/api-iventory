<?php

namespace App\Console\Commands;

use App\Models\Employes;
use Illuminate\Console\Command;

class DeactivateExpiredContracts extends Command
{
    protected $signature = 'employes:deactivate-expired';

    protected $description = 'Nonaktifkan karyawan yang resign terjadwal sudah tiba atau kontraknya habis tanpa perpanjangan';

    public function handle(): int
    {
        $today = now()->toDateString();
        $resigned = 0;
        $ended = 0;

        // (a) resign terjadwal yang tanggalnya sudah tiba
        Employes::query()
            ->where('status', 'active')
            ->where('exit_type', 'resigned')
            ->whereDate('left_at', '<=', $today)
            ->eachById(function (Employes $e) use (&$resigned) {
                $e->update(['status' => 'inactive']);
                $resigned++;
            });

        // (b) kontrak habis tanpa perpanjangan dan tanpa resign terjadwal
        Employes::query()
            ->where('status', 'active')
            ->whereNotNull('contract_end')
            ->whereDate('contract_end', '<', $today)
            ->where(fn ($q) => $q->whereNull('exit_type')->orWhere('exit_type', '!=', 'resigned'))
            ->eachById(function (Employes $e) use (&$ended) {
                $e->update([
                    'status' => 'inactive',
                    'left_at' => $e->contract_end,
                    'exit_type' => 'contract_ended',
                ]);
                $ended++;
            });

        $this->info("Resign dinonaktifkan: {$resigned}, kontrak berakhir dinonaktifkan: {$ended}");

        return self::SUCCESS;
    }
}
