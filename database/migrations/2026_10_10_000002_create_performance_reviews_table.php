<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employes_id')->constrained('employes')->cascadeOnDelete();
            $table->string('project')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('reviewer_name');
            $table->string('reviewer_title')->nullable();
            $table->date('review_date');
            // skor per KRA: {"safety":[..10],"production":[..11],"cost":[..3]}
            $table->json('scores');
            $table->decimal('safety_avg', 3, 2);
            $table->decimal('production_avg', 3, 2);
            $table->decimal('cost_avg', 3, 2);
            $table->decimal('overall_avg', 3, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
