<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_cpd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employes_id')->unique()->constrained('employes')->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->text('nik_ktp')->nullable();
            $table->text('npwp')->nullable();
            $table->string('bpjs_labour_no')->nullable();
            $table->string('phone')->nullable();
            $table->text('home_address')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('department')->nullable();
            $table->string('employee_type')->nullable();
            $table->string('ptfi_assigned_uid')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_cpd');
    }
};
