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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('part_number');
            $table->string('file')->nullable();
            $table->string('name');
            $table->enum('category', ['apd','tools', 'others'])->default('apd');
            $table->string('brand')->nullable();
            $table->string('type')->nullable();
            $table->string('size')->nullable();
            // $table->string('size', [
            //     's',
            //     'm',
            //     'l',
            //     'xl',
            //     'xxl',
            //     'universal'])->nullable();
            $table->enum('unit', ['pcs', 'set', 'unit', 'pair', 'others'])->default('pcs');
            $table->integer('min_stock')->nullable();
            $table->integer('current_stock')->nullable();
            $table->enum('status', ['available', 'low_stock', 'out_of_stock'])->default('available')->nullable();
            $table->text('description')->nullable();
            $table->string('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */ 
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
