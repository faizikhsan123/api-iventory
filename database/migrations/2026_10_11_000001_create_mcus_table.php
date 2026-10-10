<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employes_id')->constrained('employes')->cascadeOnDelete();
            $table->string('place_name');
            $table->string('mcu_name')->nullable();
            $table->date('mcu_date');
            $table->string('document')->nullable();
            $table->text('summary')->nullable();
            $table->text('allergies')->nullable();
            $table->date('next_mcu_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcus');
    }
};
