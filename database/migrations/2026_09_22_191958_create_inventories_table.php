<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->bigIncrements('inventory_id');

            $table->foreignId('product_id')
                ->unique()
                ->constrained('products', 'product_id')
                ->restrictOnDelete();

            $table->decimal('quantity_on_hand', 15, 6)->default(0);
            $table->decimal('reorder_level', 15, 6)->default(0);

            $table->timestamp('last_updated')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};