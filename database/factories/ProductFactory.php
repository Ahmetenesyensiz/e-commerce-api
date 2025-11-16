<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category; // <-- BUNU EKLE

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true), // Örn: "Yeni Nesil Laptop"
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(100, 50000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'category_id' => Category::factory() // Otomatik olarak yeni bir kategori oluşturur
        ];
    }
}