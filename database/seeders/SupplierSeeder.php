<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'Davao Building Materials Supply',
                'contact_number' => '09171234567',
                'address' => 'Davao City',
            ],
            [
                'supplier_name' => 'Mindanao Lumber Trading',
                'contact_number' => '09281234567',
                'address' => 'Davao City',
            ],
            [
                'supplier_name' => 'Southern Hardware Distributor',
                'contact_number' => '09391234567',
                'address' => 'Davao City',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}