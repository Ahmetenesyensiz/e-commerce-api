<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kullanıcıları Oluştur
        // Admin Kullanıcısı
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin'
        ]);

        // Normal Müşteri
        User::create([
            'name' => 'Normal User',
            'email' => 'user@test.com',
            'password' => Hash::make('user123'),
            'role' => 'user'
        ]);

        // 2. Kategoriler ve Gerçekçi Ürünler
        $data = [
            'Elektronik' => [
                'description' => 'En son teknoloji cihazlar ve aksesuarlar.',
                'products' => [
                    ['name' => 'iPhone 15 Pro Max', 'price' => 85000],
                    ['name' => 'MacBook Air M2', 'price' => 42000],
                    ['name' => 'Samsung Galaxy S24', 'price' => 55000],
                    ['name' => 'Sony WH-1000XM5 Kulaklık', 'price' => 12000],
                    ['name' => 'iPad Air 5. Nesil', 'price' => 23000],
                ]
            ],
            'Giyim' => [
                'description' => 'Kadın ve erkek modasının öncü ürünleri.',
                'products' => [
                    ['name' => 'Mavi Slim Fit Kot Pantolon', 'price' => 1200],
                    ['name' => 'Nike Air Force 1 Ayakkabı', 'price' => 4500],
                    ['name' => 'Zara Basic Beyaz T-Shirt', 'price' => 450],
                    ['name' => 'North Face Kışlık Mont', 'price' => 15000],
                    ['name' => 'Deri Ceket', 'price' => 3500],
                ]
            ],
            'Kitap' => [
                'description' => 'Dünya klasikleri ve çok satan romanlar.',
                'products' => [
                    ['name' => 'Harry Potter ve Felsefe Taşı', 'price' => 250],
                    ['name' => 'Yüzüklerin Efendisi: Yüzük Kardeşliği', 'price' => 300],
                    ['name' => '1984 - George Orwell', 'price' => 120],
                    ['name' => 'Simyacı - Paulo Coelho', 'price' => 150],
                    ['name' => 'Suç ve Ceza - Dostoyevski', 'price' => 180],
                ]
            ]
        ];

        // Döngüyle Veritabanına Ekleme
        foreach ($data as $categoryName => $catDetails) {
            // Kategoriyi Oluştur
            $category = Category::create([
                'name' => $categoryName,
                'description' => $catDetails['description']
            ]);

            // O Kategorinin Ürünlerini Ekle
            foreach ($catDetails['products'] as $product) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'description' => "Bu harika {$product['name']} ürünü şimdi stoklarımızda.",
                    'price' => $product['price'],
                    'stock_quantity' => rand(10, 50) // Stok 10 ile 50 arasında rastgele olsun
                ]);
            }
        }
    }
}