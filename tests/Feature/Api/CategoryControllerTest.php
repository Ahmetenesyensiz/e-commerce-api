<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use PHPUnit\Framework\Attributes\Test;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    // Her testten önce Admin ve User oluştur
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    #[Test]
    public function public_user_can_list_categories()
    {
        // 1 kategori oluşturalım
        Category::factory()->create(['name' => 'Test Kategori']);

        // Token olmadan istek at
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ])
                 ->assertJsonCount(1, 'data'); // 1 kategori olmalı
    }

    #[Test]
    public function non_admin_user_cannot_create_category()
    {
        // Normal user olarak istek at
        $response = $this->actingAs($this->user, 'sanctum')
                         ->postJson('/api/categories', [
                             'name' => 'Yetkisiz Kategori',
                             'description' => 'Girememeli'
                         ]);
        
        // 403 (Yasak) hatası almalı
        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_create_category()
    {
        // Admin olarak istek at
        $response = $this->actingAs($this->admin, 'sanctum')
                         ->postJson('/api/categories', [
                             'name' => 'Admin Kategori',
                             'description' => 'Başarılı olmalı'
                         ]);
        
        $response->assertStatus(201) // 201 Oluşturuldu mu?
                 ->assertJson(['success' => true]);

        // Veritabanını kontrol et
        $this->assertDatabaseHas('categories', [
            'name' => 'Admin Kategori'
        ]);
    }
}