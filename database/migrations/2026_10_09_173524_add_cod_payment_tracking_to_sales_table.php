<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('PAY_NOW');
            $table->string('payment_status', 20)->default('PAID');
            $table->decimal('payment_received', 15, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('delivery_status', 20)->default('NOT_REQUIRED');
        });

        DB::table('sales')->where('delivery_required', true)->where('status', 'PENDING')
            ->update(['delivery_status' => 'PENDING']);
        DB::table('sales')->where('delivery_required', true)->where('status', 'COMPLETED')
            ->update(['delivery_status' => 'DELIVERED']);
        DB::table('sales')->where('delivery_required', true)->where('status', 'CANCELLED')
            ->update(['delivery_status' => 'CANCELLED']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'payment_received', 'paid_at', 'delivery_status']);
        });
    }
};
