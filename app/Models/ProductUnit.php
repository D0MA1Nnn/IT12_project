<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $table = 'product_units';

    protected $primaryKey = 'product_unit_id';

    protected $fillable = [
        'product_id',
        'unit_id',
        'selling_price',
        'purchase_cost',
        'conversion_factor',
        'is_base_unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'purchase_cost' => 'decimal:2',
            'conversion_factor' => 'decimal:6',
            'is_base_unit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }

    public function unit()
    {
        return $this->belongsTo(
            UnitOfMeasure::class,
            'unit_id',
            'unit_id'
        );
    }

    public function purchaseItems()
    {
        return $this->hasMany(
            PurchaseItem::class,
            'product_unit_id',
            'product_unit_id'
        );
    }

    public function saleItems()
    {
        return $this->hasMany(
            SaleItem::class,
            'product_unit_id',
            'product_unit_id'
        );
    }
}