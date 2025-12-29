<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::create([
            'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
            'slug' => 'cod',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'name' => ['en' => 'Bank Transfer', 'sk' => 'Bankový prevod'],
            'slug' => 'bank-transfer',
            'is_active' => true,
        ]);
    }
}
