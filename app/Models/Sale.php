<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'sales';

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'user_id',
        'sale_date',
        'total_amount',
        'status',
        'delivery_required',
        'delivery_fee',
        'customer_name',
        'customer_contact_number',
        'delivery_address',
        'delivery_cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'delivery_required' => 'boolean',
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            SaleItem::class,
            'sale_id',
            'sale_id'
        );
    }
}
