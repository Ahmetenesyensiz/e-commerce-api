<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;

// PUBLIC ROTALAR (Herkese Açık)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']); // Kategoriler
Route::get('/products', [ProductController::class, 'index']);    // Ürün Listesi (Filtreli)
Route::get('/products/{id}', [ProductController::class, 'show']); // Ürün Detayı

// SİPARİŞ İŞLEMLERİ
    Route::post('/orders', [OrderController::class, 'store']); // Sipariş Ver
    Route::get('/orders', [OrderController::class, 'index']);  // Siparişlerim
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Sipariş Detayı

// KORUMALI ROTALAR (Token Gerekli)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']); // Profil Güncelleme
    Route::post('/logout', [AuthController::class, 'logout']);

    // SEPET İŞLEMLERİ (Normal Kullanıcılar Erişebilir)
    Route::get('/cart', [CartController::class, 'index']);              // Sepeti Gör
    Route::post('/cart/add', [CartController::class, 'add']);           // Sepete Ekle
    Route::put('/cart/update', [CartController::class, 'update']);      // Miktar Güncelle
    Route::delete('/cart/remove/{product_id}', [CartController::class, 'remove']); // Ürün Çıkar
    Route::delete('/cart/clear', [CartController::class, 'clear']);     // Sepeti Temizle

    // SİPARİŞ İŞLEMLERİ
    Route::post('/orders', [OrderController::class, 'store']); // Sipariş Ver
    Route::get('/orders', [OrderController::class, 'index']);  // Siparişlerim
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Sipariş Detayı

    // ADMIN İŞLEMLERİ
    Route::middleware('is_admin')->group(function () {
        // Kategori Yönetimi
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Ürün Yönetimi (BUNLARI EKLE)
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Sipariş Yönetimi
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // Sipariş Durumu Güncelle
    });

});