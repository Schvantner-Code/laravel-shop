<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['en' => 'Notebooks', 'sk' => 'Zošity'],
                'slug' => 'notebooks',
            ],
            [
                'name' => ['en' => 'Pens & Pencils', 'sk' => 'Perá a ceruzky'],
                'slug' => 'pens',
            ],
            [
                'name' => ['en' => 'Desk Accessories', 'sk' => 'Doplnky na stôl'],
                'slug' => 'accessories',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
