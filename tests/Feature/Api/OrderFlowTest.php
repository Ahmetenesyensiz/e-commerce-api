<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_add_to_cart_and_create_order()
    {
        // 1. Hazırlık: User ve Ürün oluştur
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 100]);

        // 2. Aksiyon: Sepete Ekle
        $this->actingAs($user, 'sanctum')
             ->postJson('/api/cart/add', [
                 'product_id' => $product->id,
                 'quantity' => 2
             ])
             ->assertStatus(200); // Sepete eklendi mi?

        // 3. Aksiyon: Sipariş Ver
        $this->actingAs($user, 'sanctum')
             ->postJson('/api/orders')
             ->assertStatus(201); // Sipariş oluştu mu?

        // 4. Doğrulama: Stok düştü mü? (Bonus Testi)
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8 // 10 - 2 = 8
        ]);

        // 5. Doğrulama: Sepet temizlendi mi?
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id
        ]);
    }
}