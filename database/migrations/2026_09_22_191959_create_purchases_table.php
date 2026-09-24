<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->bigIncrements('purchase_id');

            $table->foreignId('supplier_id')
                ->constrained('suppliers', 'supplier_id')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->restrictOnDelete();

            $table->dateTime('purchase_date');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('status', [
                'PENDING',
                'COMPLETED',
                'CANCELLED',
            ])->default('PENDING');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};