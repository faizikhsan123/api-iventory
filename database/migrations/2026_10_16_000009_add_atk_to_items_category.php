<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tambah kategori 'atk' pada items.category (nullability dan default tetap: NOT NULL DEFAULT 'apd').
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY category ENUM('apd','tools','atk','others') NOT NULL DEFAULT 'apd'");
    }

    public function down(): void
    {
        // Pindahkan baris 'atk' ke 'others' agar perubahan enum tidak gagal.
        DB::table('items')->where('category', 'atk')->update(['category' => 'others']);

        DB::statement("ALTER TABLE items MODIFY category ENUM('apd','tools','others') NOT NULL DEFAULT 'apd'");
    }
};
