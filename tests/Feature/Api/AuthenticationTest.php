<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase; // Veritabanını her testten sonra sıfırlar
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test; // <-- BUNU EKLE

class AuthenticationTest extends TestCase
{
    // Bu 'trait', her test çalıştırdığında :memory: veritabanını
    // migrate:fresh yapmamızı sağlar.
    use RefreshDatabase;

     #[Test]
     
    public function user_can_register_successfully()
    {
        // 1. Hazırlık (Data)
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 2. Aksiyon (API'ye istek at)
        $response = $this->postJson('/api/register', $userData);

        // 3. Doğrulama (Assert)
        $response
            ->assertStatus(201) // 201 Oluşturuldu mu?
            ->assertJson([
                'success' => true,
                'message' => 'Kullanıcı başarıyla oluşturuldu'
            ]);

        // Ekstra Kontrol: Veritabanına gerçekten kayıt oldu mu?
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    #[Test]
    
    public function user_can_login_successfully()
    {
        // 1. Hazırlık (Önce bir kullanıcı oluşturalım)
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'user'
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 2. Aksiyon (Giriş yapmayı dene)
        $response = $this->postJson('/api/login', $loginData);

        // 3. Doğrulama
        $response
            ->assertStatus(200) // Başarılı mı?
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.user.email', 'test@example.com'); // Gelen veri doğru mu?
    }
}