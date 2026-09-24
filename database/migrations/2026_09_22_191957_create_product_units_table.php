<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->bigIncrements('product_unit_id');

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units_of_measure', 'unit_id')
                ->restrictOnDelete();

            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('conversion_factor', 15, 6)->default(1);

            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['product_id', 'unit_id'],
                'product_units_product_id_unit_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};