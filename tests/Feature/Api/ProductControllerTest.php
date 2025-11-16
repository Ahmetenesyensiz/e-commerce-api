<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function admin_can_create_product()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
                         ->postJson('/api/products', [
                             'name' => 'Yeni Laptop',
                             'price' => 25000,
                             'stock_quantity' => 10,
                             'category_id' => $category->id
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Yeni Laptop']);
    }

    #[Test]
    public function product_list_can_be_filtered()
    {
        $category = Category::factory()->create();
        
        $productA = Product::factory()->create([
            'name' => 'Pahalı Laptop',
            'price' => 50000,
            'category_id' => $category->id
        ]);
        
        $productB = Product::factory()->create([
            'name' => 'Ucuz Laptop',
            'price' => 10000,
            'category_id' => $category->id
        ]);

        // Filtre: Fiyatı 40000'den yüksek olanı getir
        $response = $this->getJson('/api/products?min_price=40000');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data') // Sayfalama olduğu için data.data
                 ->assertJsonPath('data.data.0.name', 'Pahalı Laptop');
    }
}