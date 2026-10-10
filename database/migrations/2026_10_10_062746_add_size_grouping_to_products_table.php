<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('group_name', 150)->nullable();
            $table->string('size_name', 60)->nullable();
            $table->unique(['category_id', 'group_name', 'size_name'], 'products_category_group_size_unique');
        });

        DB::table('products')->select('product_id', 'product_name')->orderBy('product_id')
            ->chunkById(100, function (Collection $products): void {
                foreach ($products as $product) {
                    if (preg_match('/^(\d+(?:\.\d+)?\s*[x×]\s*\d+(?:\.\d+)?(?:\s*[x×]\s*\d+(?:\.\d+)?)?)\s+(.+)$/iu', $product->product_name, $matches)) {
                        $groupName = trim($matches[2]);
                        $sizeName = trim($matches[1]);
                    } elseif (preg_match('/^(.+?)\s+(\d+(?:\/\d+|\.\d+)?\s*(?:"|″|mm|cm|ft|inches?))$/iu', $product->product_name, $matches)) {
                        $groupName = trim($matches[1]);
                        $sizeName = trim($matches[2]);
                    } else {
                        continue;
                    }

                    DB::table('products')->where('product_id', $product->product_id)->update([
                        'group_name' => $groupName,
                        'size_name' => $sizeName,
                    ]);
                }
            }, 'product_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_category_group_size_unique');
            $table->dropColumn(['group_name', 'size_name']);
        });
    }
};
