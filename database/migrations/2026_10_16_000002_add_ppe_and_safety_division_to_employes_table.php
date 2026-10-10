<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $ppe = ['ppe_shoes', 'ppe_coverall', 'ppe_wearpack', 'ppe_respirator', 'ppe_vest', 'ppe_gloves'];

    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            foreach ($this->ppe as $col) {
                $table->string($col, 30)->nullable();
            }
        });

        DB::statement("ALTER TABLE employes MODIFY division ENUM('Gas Analyzer','I&C-PMR','I&C-ER','Dryer','Safety') NOT NULL DEFAULT 'Gas Analyzer'");
    }

    public function down(): void
    {
        if (DB::table('employes')->whereIn('division', ['Safety', 'Dryer'])->exists()) {
            throw new RuntimeException("Rollback dibatalkan: ada employes.division 'Safety'/'Dryer'. Ubah datanya dulu secara manual.");
        }

        DB::statement("ALTER TABLE employes MODIFY division ENUM('Gas Analyzer','I&C-PMR','I&C-ER') NOT NULL DEFAULT 'Gas Analyzer'");

        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn($this->ppe);
        });
    }
};
