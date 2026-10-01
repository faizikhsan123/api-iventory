<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Rp 15.000" -> 15000 (butuh MySQL 8+ / MariaDB 10.0.5+)
        DB::statement("UPDATE items SET price = COALESCE(NULLIF(REGEXP_REPLACE(price, '[^0-9]', ''), ''), '0')");

        Schema::table('items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->change();
            $table->decimal('avg_price', 15, 2)->default(0)->after('price');
        });

        DB::statement('UPDATE items SET avg_price = price');

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('qty');
        });

        Schema::create('item_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_price', 15, 2)->default(0);
            $table->decimal('new_price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_price_histories');

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('avg_price');
            $table->string('price')->nullable()->change();
        });
    }
};