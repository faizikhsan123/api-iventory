<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Index untuk kolom yang dipakai filter/urut di dashboard dan halaman baru.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->index(['status', 'contract_end'], 'employes_status_contract_end_index');
        });

        Schema::table('mcus', function (Blueprint $table) {
            $table->index(['employes_id', 'mcu_date'], 'mcus_employes_mcu_date_index');
            $table->index('next_mcu_date', 'mcus_next_mcu_date_index');
        });

        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->index(['employes_id', 'review_date'], 'performance_reviews_employes_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->dropIndex('performance_reviews_employes_date_index');
        });

        Schema::table('mcus', function (Blueprint $table) {
            $table->dropIndex('mcus_next_mcu_date_index');
            $table->dropIndex('mcus_employes_mcu_date_index');
        });

        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex('employes_status_contract_end_index');
        });
    }
};
