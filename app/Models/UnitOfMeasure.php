<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'units_of_measure';

    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'unit_name',
        'unit_symbol',
        'unit_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function productUnits()
    {
        return $this->hasMany(
            ProductUnit::class,
            'unit_id',
            'unit_id'
        );
    }
}