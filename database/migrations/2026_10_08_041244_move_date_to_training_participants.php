<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('date');
        });

        Schema::table('training_participants', function (Blueprint $table) {
            $table->date('date')->nullable()->after('employes_id');
        });
    }

    public function down(): void
    {
        Schema::table('training_participants', function (Blueprint $table) {
            $table->dropColumn('date');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dateTime('date')->nullable();
        });
    }
};