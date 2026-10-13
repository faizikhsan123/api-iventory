<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// left_at (migrasi 2026_10_12) dipakai ulang sebagai tanggal resign/keluar.
// exit_type: resigned | contract_ended (divalidasi di aplikasi).
// Index (status, left_at) untuk query turnover per periode.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->string('resign_reason', 255)->nullable()->after('left_at');
            $table->string('exit_type')->nullable()->after('resign_reason');
            $table->index(['status', 'left_at'], 'employes_status_left_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex('employes_status_left_at_index');
            $table->dropColumn(['resign_reason', 'exit_type']);
        });
    }
};
