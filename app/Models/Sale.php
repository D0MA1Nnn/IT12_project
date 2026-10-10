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
        'payment_method',
        'payment_status',
        'payment_received',
        'paid_at',
        'delivery_status',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'delivery_required' => 'boolean',
            'delivery_fee' => 'decimal:2',
            'payment_received' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function receivedPayment(): float
    {
        return (float) ($this->payment_received ?? ($this->payment_status === 'PAID' ? $this->total_amount : 0));
    }

    public function amountDue(): float
    {
        return $this->payment_status === 'PAID' || $this->status === 'CANCELLED'
            ? 0.0
            : (float) $this->total_amount;
    }

    public function paymentChange(): float
    {
        return $this->payment_status === 'PAID'
            ? round(max(0, $this->receivedPayment() - (float) $this->total_amount), 2)
            : 0.0;
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
