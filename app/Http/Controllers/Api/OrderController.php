<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Transaction için gerekli

class OrderController extends Controller
{
    // 1. SİPARİŞ OLUŞTUR (POST /api/orders)
    public function store()
    {
        $user = Auth::user();
        
        // Kullanıcının sepetini bul
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Sepetiniz boş'], 400);
        }

        // DB Transaction Başlat (Hata olursa işlemi geri almak için)
        return DB::transaction(function () use ($user, $cart) {
            
            $totalAmount = 0;
            
            // 1. Stok Kontrolü ve Toplam Tutar Hesaplama
            foreach ($cart->items as $item) {
                if ($item->product->stock_quantity < $item->quantity) {
                    // Hata fırlatıp işlemi iptal ediyoruz
                    throw new \Exception("Üzgünüz, {$item->product->name} ürünü için yeterli stok yok.");
                }
                $totalAmount += $item->product->price * $item->quantity;
            }

            // 2. Siparişi Oluştur
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            // 3. Sipariş Detaylarını Ekle ve STOKTAN DÜŞ
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price // O anki fiyatı kaydediyoruz
                ]);

                // BONUS: Stoktan düşme işlemi
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            // 4. Sepeti Temizle
            CartItem::where('cart_id', $cart->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sipariş başarıyla oluşturuldu',
                'data' => $order
            ], 201);

        });
    }

    // 2. KULLANICININ SİPARİŞLERİNİ LİSTELE (GET /api/orders)
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Siparişler listelendi',
            'data' => $orders
        ], 200);
    }

    // 3. SİPARİŞ DETAYI (GET /api/orders/{id})
    public function show($id)
    {
        $order = Order::with('items.product')
                      ->where('user_id', Auth::id())
                      ->where('id', $id)
                      ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Sipariş bulunamadı'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sipariş detayı getirildi',
            'data' => $order
        ], 200);
    }
}