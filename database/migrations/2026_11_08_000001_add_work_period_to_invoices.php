<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('contract_number', 150)->nullable();
            $table->date('work_period_start')->nullable();
            $table->date('work_period_end')->nullable();
            $table->unsignedTinyInteger('week_number')->nullable();
            $table->index(['contract_number', 'work_period_start'], 'invoices_contract_period_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_contract_period_index');
            $table->dropColumn(['contract_number', 'work_period_start', 'work_period_end', 'week_number']);
        });
    }
};
