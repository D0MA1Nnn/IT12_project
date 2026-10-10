<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('conversion_factor', 17, 8)->default(1)->change();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 17, 8)->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 17, 8)->change();
        });
    }

    /**
     * Reverse the migrations, reducing stored precision back to six decimal places.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 6)->change();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 15, 6)->default(0)->change();
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('conversion_factor', 15, 6)->default(1)->change();
        });
    }
};
