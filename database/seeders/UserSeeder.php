<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'owner',
            'password_hash' => Hash::make('owner123'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'salesclerk',
            'password_hash' => Hash::make('clerk123'),
            'role' => 'SALES_CLERK',
            'is_active' => true,
        ]);
    }
}