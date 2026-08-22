<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transactions_id')
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('items_id')
                ->constrained('items')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('qty');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
