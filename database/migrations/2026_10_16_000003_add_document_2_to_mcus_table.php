<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcus', function (Blueprint $table) {
            $table->string('document_2')->nullable()->after('document');
        });
    }

    public function down(): void
    {
        Schema::table('mcus', function (Blueprint $table) {
            $table->dropColumn('document_2');
        });
    }
};
