<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// division_training sudah punya 'Safety'; tambahkan 'Dryer' agar daftar divisi sama dengan employes.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trainings MODIFY division_training ENUM('Gas Analyzer','I&C-PMR','I&C-ER','Safety','Dryer') NOT NULL DEFAULT 'Gas Analyzer'");
    }

    public function down(): void
    {
        if (DB::table('trainings')->where('division_training', 'Dryer')->exists()) {
            throw new RuntimeException("Rollback dibatalkan: ada trainings.division_training = 'Dryer'.");
        }

        DB::statement("ALTER TABLE trainings MODIFY division_training ENUM('Gas Analyzer','I&C-PMR','I&C-ER','Safety') NOT NULL DEFAULT 'Gas Analyzer'");
    }
};
