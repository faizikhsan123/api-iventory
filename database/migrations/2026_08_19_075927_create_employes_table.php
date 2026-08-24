<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('division', [
                'GA',
                'HRD',
                'IT',
                'Finance',
                'Produksi',
                'Warehouse',
            ])->default('HRD');
            $table->enum('position', [
                'Staff',
                'Supervisor',
                'Manager',
                'Admin',
                'Operator',
                'Teknisi',
            ])->default('Staff');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
