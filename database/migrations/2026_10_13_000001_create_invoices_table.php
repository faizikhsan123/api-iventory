<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('title');                       // nama / jenis jasa
            $table->string('division');                    // PMR, ER, Gas, Dryer
            $table->string('client')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status')->default('draft');
            $table->date('invoice_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['division', 'status']);
        });

        // riwayat perpindahan status (untuk melihat invoice sudah sampai mana)
        Schema::create('invoice_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->date('status_date');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_status_logs');
        Schema::dropIfExists('invoices');
    }
};
