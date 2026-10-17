<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rfq_updates');
        Schema::dropIfExists('rfqs');
    }

    public function down(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_no')->unique();
            $table->date('rfq_date');
            $table->string('type')->nullable();
            $table->string('source')->nullable();
            $table->string('area')->nullable();
            $table->string('opportunity_name');
            $table->text('description')->nullable();
            $table->string('customer_ref')->nullable();
            $table->string('quote_no')->nullable();
            $table->string('customer');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('supplier')->nullable();
            $table->boolean('has_supplier_quote')->default(false);
            $table->boolean('has_brochure')->default(false);
            $table->boolean('has_drawing')->default(false);
            $table->string('status')->default('pending');
            $table->string('priority_code');
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

        Schema::create('rfq_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->date('update_date');
            $table->text('note');
            $table->string('priority_code')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['rfq_id', 'update_date']);
        });
    }
};
