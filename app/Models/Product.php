<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'product_name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
}