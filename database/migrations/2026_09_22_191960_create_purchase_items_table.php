<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->bigIncrements('purchase_item_id');

            $table->foreignId('purchase_id')
                ->constrained('purchases', 'purchase_id')
                ->restrictOnDelete();

            $table->foreignId('product_unit_id')
                ->constrained('product_units', 'product_unit_id')
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 6);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};