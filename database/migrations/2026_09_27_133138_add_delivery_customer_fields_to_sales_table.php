<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_name', 150)
                ->nullable()
                ->after('delivery_required');

            $table->string('customer_contact_number', 50)
                ->nullable()
                ->after('customer_name');

            $table->text('delivery_address')
                ->nullable()
                ->after('customer_contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_contact_number',
                'delivery_address',
            ]);
        });
    }
};
