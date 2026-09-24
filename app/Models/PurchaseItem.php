<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_items';

    protected $primaryKey = 'purchase_item_id';

    protected $fillable = [
        'purchase_id',
        'product_unit_id',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(
            Purchase::class,
            'purchase_id',
            'purchase_id'
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