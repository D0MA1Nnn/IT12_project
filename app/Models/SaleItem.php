<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $primaryKey = 'sale_item_id';

    protected $fillable = [
        'sale_id',
        'product_unit_id',
        'quantity',
        'unit_price',
        'subtotal',
        'selling_details',
        'alteration',
        'alteration_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'selling_details' => 'array',
            'alteration' => 'array',
        ];
    }

    public function sellingQuantity(): float
    {
        return (float) ($this->selling_details['quantity'] ?? $this->alteration['quantity'] ?? $this->quantity);
    }

    public function sellingUnitName(): string
    {
        return $this->selling_details['unit'] ?? $this->alteration['unit'] ?? $this->productUnit?->unit?->unit_name ?? 'Unit';
    }

    public function sellingUnitPrice(): float
    {
        return (float) ($this->selling_details['unit_price'] ?? $this->unit_price);
    }

    public function baseStockQuantity(): float
    {
        return (float) ($this->selling_details['base_quantity'] ?? $this->alteration['base_quantity']
            ?? ((float) $this->quantity * (float) ($this->productUnit?->conversion_factor ?? 0)));
    }

    public function sale()
    {
        return $this->belongsTo(
            Sale::class,
            'sale_id',
            'sale_id'
        );
    }

    public function productUnit()
    {
        return $this->belongsTo(
            ProductUnit::class,
            'product_unit_id',
            'product_unit_id'
        );
    }
}
