<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tambah spesialis 'atk' pada suppliers.spesialis (nullability tetap: NOT NULL, tanpa default).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY spesialis ENUM('apd','tools','atk','others') NOT NULL");
    }

    public function down(): void
    {
        // Pindahkan baris 'atk' ke 'others' agar perubahan enum tidak gagal.
        DB::table('suppliers')->where('spesialis', 'atk')->update(['spesialis' => 'others']);

        DB::statement("ALTER TABLE suppliers MODIFY spesialis ENUM('apd','tools','others') NOT NULL");
    }
};
