<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_no')->unique();          // RFQ20240112-003
            $table->date('rfq_date');
            $table->string('type')->nullable();              // Dust Collector, ...
            $table->string('source')->nullable();
            $table->string('area')->nullable();              // Tembagapura, ...
            $table->string('opportunity_name');
            $table->text('description')->nullable();         // Description Project
            $table->string('customer_ref')->nullable();      // Customer Ref#
            $table->string('quote_no')->nullable();          // Quote#
            $table->string('customer');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('supplier')->nullable();

            // Customer Inquiry: dokumen yang sudah diterima
            $table->boolean('has_supplier_quote')->default(false);
            $table->boolean('has_brochure')->default(false);
            $table->boolean('has_drawing')->default(false);

            $table->string('status')->default('pending');    // pending, won, lost, on_hold
            $table->string('priority_code');                 // A1..E (lihat Rfq::PRIORITIES)

            // Received Order
            $table->boolean('po_received')->default(false);
            $table->string('po_number')->nullable();

            $table->string('current_pic')->nullable();
            $table->text('action_plan')->nullable();
            $table->date('deadline')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();

            $table->index('rfq_date');
            $table->index(['status', 'priority_code']);
            $table->index('customer');
        });

        // kolom "Status" di log: catatan progres bertanggal ("23 Jan : Assessment report receive ...")
        Schema::create('rfq_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->date('update_date');
            $table->text('note');
            $table->string('priority_code')->nullable();     // prioritas saat update dibuat
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['rfq_id', 'update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_updates');
        Schema::dropIfExists('rfqs');
    }
};
