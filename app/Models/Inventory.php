<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $primaryKey = 'inventory_id';

    protected $fillable = [
        'product_id',
        'quantity_on_hand',
        'reorder_level',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:6',
            'reorder_level' => 'decimal:6',
            'last_updated' => 'datetime',
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
}