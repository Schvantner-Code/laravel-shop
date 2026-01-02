<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => [
                'en' => $this->faker->word,
                'sk' => $this->faker->word,
            ],
            'description' => [
                'en' => $this->faker->sentence,
                'sk' => $this->faker->sentence,
            ],
            'price' => $this->faker->numberBetween(100, 10000),
            'is_active' => true,
        ];
    }
}
