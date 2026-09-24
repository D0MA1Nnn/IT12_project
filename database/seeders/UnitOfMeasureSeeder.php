<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnitOfMeasure;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'unit_name' => 'Piece',
                'unit_symbol' => 'pc',
                'unit_type' => 'COUNT',
            ],
            [
                'unit_name' => 'Meter',
                'unit_symbol' => 'm',
                'unit_type' => 'LENGTH',
            ],
            [
                'unit_name' => 'Centimeter',
                'unit_symbol' => 'cm',
                'unit_type' => 'LENGTH',
            ],
            [
                'unit_name' => 'Kilogram',
                'unit_symbol' => 'kg',
                'unit_type' => 'WEIGHT',
            ],
            [
                'unit_name' => 'Bag',
                'unit_symbol' => 'bag',
                'unit_type' => 'COUNT',
            ],
            [
                'unit_name' => 'Box',
                'unit_symbol' => 'box',
                'unit_type' => 'COUNT',
            ],
        ];

        foreach ($units as $unit) {
            UnitOfMeasure::create($unit);
        }
    }
}