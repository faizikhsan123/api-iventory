<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            // tanggal karyawan keluar (untuk turn over rate); terisi saat status jadi inactive
            $table->date('left_at')->nullable()->after('contract_end');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn('left_at');
        });
    }
};
