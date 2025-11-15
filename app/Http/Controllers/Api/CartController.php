<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // 1. SEPETİ GÖRÜNTÜLE (GET /api/cart)
    public function index()
    {
        $user = Auth::user();
        
        // Kullanıcının sepeti var mı? Yoksa oluştur.
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Sepeti, içindeki ürünlerle (ve ürün detaylarıyla) getir
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Sepet görüntülendi',
            'data' => $cart
        ], 200);
    }

    // 2. SEPETE ÜRÜN EKLE (POST /api/cart/add)
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        // Ürün stok kontrolü (Bonus: Fonksiyonel Bonuslar) [cite: 103]
        $product = Product::find($request->product_id);
        if ($product->stock_quantity < $request->quantity) {
             return response()->json(['success' => false, 'message' => 'Yetersiz stok!'], 400);
        }

        // Ürün zaten sepette var mı?
        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if ($cartItem) {
            // Varsa miktarını artır
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Yoksa yeni ekle
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ürün sepete eklendi'
        ], 200);
    }

    // 3. ÜRÜN MİKTARI GÜNCELLE (PUT /api/cart/update)
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Sepet bulunamadı'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Ürün sepette bulunamadı'], 404);
        }

        // Stok Kontrolü (Yine Bonus!)
        $product = Product::find($request->product_id);
        if ($product->stock_quantity < $request->quantity) {
             return response()->json(['success' => false, 'message' => 'Yetersiz stok!'], 400);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Sepet güncellendi'
        ], 200);
    }

    // 4. SEPETTEN ÜRÜN ÇIKAR (DELETE /api/cart/remove/{product_id})
    public function remove($product_id)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) return response()->json(['success' => false, 'message' => 'Sepet boş'], 404);

        $deleted = CartItem::where('cart_id', $cart->id)
                           ->where('product_id', $product_id)
                           ->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Ürün sepetten çıkarıldı'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Ürün bulunamadı'], 404);
    }

    // 5. SEPETİ TEMİZLE (DELETE /api/cart/clear)
    public function clear()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Sepet temizlendi'], 200);
    }
}