<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/cart",
     * summary="Sepeti Görüntüleme",
     * tags={"Cart"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Sepet görüntülendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sepet görüntülendi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
    public function index()
    {
        $user = Auth::user();
        
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Sepet görüntülendi',
            'data' => $cart
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/cart/add",
     * summary="Sepete Ürün Ekleme",
     * tags={"Cart"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"product_id","quantity"},
     * @OA\Property(property="product_id", type="integer", example=1),
     * @OA\Property(property="quantity", type="integer", example=2)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürün sepete eklendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün sepete eklendi")
     * )
     * ),
     * @OA\Response(response=400, description="Yetersiz stok"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=422, description="Validasyon hatası")
     * )
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        
        $product = Product::find($request->product_id);
        if ($product->stock_quantity < $request->quantity) {
             return response()->json(['success' => false, 'message' => 'Yetersiz stok!'], 400);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
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

    /**
     * @OA\Put(
     * path="/api/cart/update",
     * summary="Sepetteki Ürün Miktarını Güncelleme",
     * tags={"Cart"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"product_id","quantity"},
     * @OA\Property(property="product_id", type="integer", example=1),
     * @OA\Property(property="quantity", type="integer", example=3)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Sepet güncellendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sepet güncellendi")
     * )
     * ),
     * @OA\Response(response=400, description="Yetersiz stok"),
     * @OA\Response(response=404, description="Sepet veya ürün bulunamadı"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=422, description="Validasyon hatası")
     * )
     */
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

    /**
     * @OA\Delete(
     * path="/api/cart/remove/{product_id}",
     * summary="Sepetten Ürün Çıkarma",
     * tags={"Cart"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="product_id",
     * in="path",
     * description="Ürün ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürün sepetten çıkarıldı",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün sepetten çıkarıldı")
     * )
     * ),
     * @OA\Response(response=404, description="Sepet veya ürün bulunamadı"),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
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

    /**
     * @OA\Delete(
     * path="/api/cart/clear",
     * summary="Sepeti Temizleme",
     * tags={"Cart"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Sepet temizlendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sepet temizlendi")
     * )
     * ),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
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