<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// mcus.next_mcu_date sudah punya index (mcus_next_mcu_date_index, migrasi 2026_10_14).
// employes.contract_end hanya ada sebagai kolom kedua di index komposit (status, contract_end),
// jadi query by contract_end saja (reminder kontrak) butuh index sendiri.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->index('contract_end', 'employes_contract_end_index');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex('employes_contract_end_index');
        });
    }
};
