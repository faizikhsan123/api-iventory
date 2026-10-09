```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus kolom date jika masih ada di tabel trainings
        if (Schema::hasColumn('trainings', 'date')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }

        // Tambahkan kolom date jika belum ada
        if (!Schema::hasColumn('training_participants', 'date')) {
            Schema::table('training_participants', function (Blueprint $table) {
                $table->date('date')->nullable()->after('employes_id');
            });
        }
    }

    public function down(): void
    {
        // Hapus kolom date dari training_participants jika ada
        if (Schema::hasColumn('training_participants', 'date')) {
            Schema::table('training_participants', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }

        // Kembalikan kolom date ke trainings jika belum ada
        if (!Schema::hasColumn('trainings', 'date')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dateTime('date')->nullable();
            });
        }
    }
};
