<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('sale_id');

            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->restrictOnDelete();

            $table->dateTime('sale_date');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('status', [
                'PENDING',
                'COMPLETED',
                'CANCELLED',
            ])->default('PENDING');

            $table->boolean('delivery_required')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};