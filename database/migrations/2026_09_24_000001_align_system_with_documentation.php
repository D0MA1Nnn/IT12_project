<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('username');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contact_person', 150)->nullable()->after('supplier_name');
            $table->string('email', 150)->nullable()->after('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn(['contact_person', 'email']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['first_name', 'last_name']));
    }
};
