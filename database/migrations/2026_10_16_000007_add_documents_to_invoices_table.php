<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satu file per jenis dokumen (quote, WO, invoice): path di storage + nama file asli untuk tampilan/unduhan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('quote_document')->nullable()->after('notes');
            $table->string('quote_document_name')->nullable()->after('quote_document');
            $table->string('wo_document')->nullable()->after('quote_document_name');
            $table->string('wo_document_name')->nullable()->after('wo_document');
            $table->string('invoice_document')->nullable()->after('wo_document_name');
            $table->string('invoice_document_name')->nullable()->after('invoice_document');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'quote_document',
                'quote_document_name',
                'wo_document',
                'wo_document_name',
                'invoice_document',
                'invoice_document_name',
            ]);
        });
    }
};
