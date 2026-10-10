<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'product_name',
        'group_name',
        'size_name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function groupLabel(): string
    {
        return $this->group_name ?? $this->product_name;
    }

    public function displayGroupKey(): string
    {
        return $this->group_name !== null && $this->size_name !== null
            ? 'group:'.$this->category_id.':'.mb_strtolower($this->group_name)
            : 'product:'.$this->product_id;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Collection<int, Product>>
     */
    public static function groupForDisplay(Collection $products): Collection
    {
        return $products->groupBy(fn (Product $product): string => $product->displayGroupKey())
            ->map(fn (Collection $sizes): Collection => $sizes->sortBy('size_name', SORT_NATURAL | SORT_FLAG_CASE)->values())
            ->sort(fn (Collection $left, Collection $right): int => strnatcasecmp($left->first()->groupLabel(), $right->first()->groupLabel())
                ?: $left->first()->product_id <=> $right->first()->product_id)
            ->values();
    }

    public function category()
    {
        return $this->belongsTo(
            Category::class,
            'category_id',
            'category_id'
        );
    }

    public function productUnits()
    {
        return $this->hasMany(
            ProductUnit::class,
            'product_id',
            'product_id'
        );
    }

    public function inventory()
    {
        return $this->hasOne(
            Inventory::class,
            'product_id',
            'product_id'
        );
    }

    public function suppliers()
    {
        return $this->belongsToMany(
            Supplier::class,
            'product_supplier',
            'product_id',
            'supplier_id'
        )->withTimestamps();
    }
}
