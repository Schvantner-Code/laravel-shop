<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodSlug;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::create([
            'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
            'slug' => PaymentMethodSlug::COD,
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'name' => ['en' => 'Bank Transfer', 'sk' => 'Bankový prevod'],
            'slug' => PaymentMethodSlug::BankTransfer,
            'is_active' => true,
        ]);
    }
}
