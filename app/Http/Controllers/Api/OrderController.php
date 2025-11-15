<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Log;
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
    /**
     * @OA\Post(
     * path="/api/orders",
     * summary="Sipariş Oluşturma",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=201,
     * description="Sipariş başarıyla oluşturuldu",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sipariş başarıyla oluşturuldu"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=400, description="Sepet boş veya yetersiz stok"),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
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

            // LOGLAMA İŞLEMİ (BONUS)
            Log::info('Yeni sipariş oluşturuldu.', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => $totalAmount,
                'time' => now()
            ]);

            // EMAIL GÖNDERME (YENİ BONUS)
            // Hata olursa sistem durmasın diye try-catch içine alıyoruz
            try {
                Mail::to($user->email)->send(new OrderPlaced($order));
            } catch (\Exception $e) {
                Log::error('Mail gönderilemedi: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Sipariş başarıyla oluşturuldu',
                'data' => $order
            ], 201);

        });
    }

    /**
     * @OA\Get(
     * path="/api/orders",
     * summary="Siparişleri Listeleme",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Siparişler listelendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Siparişler listelendi"),
     * @OA\Property(property="data", type="array", @OA\Items(type="object"))
     * )
     * ),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
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

    /**
     * @OA\Get(
     * path="/api/orders/{id}",
     * summary="Sipariş Detayı",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Sipariş ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Sipariş detayı getirildi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sipariş detayı getirildi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Sipariş bulunamadı"),
     * @OA\Response(response=401, description="Yetkisiz erişim")
     * )
     */
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

    /**
     * @OA\Put(
     * path="/api/orders/{id}/status",
     * summary="Sipariş Durumunu Güncelleme",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Sipariş ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"status"},
     * @OA\Property(property="status", type="string", enum={"pending", "shipped", "delivered", "cancelled"}, example="shipped", description="Sipariş durumu")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Sipariş durumu güncellendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Sipariş durumu güncellendi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Sipariş bulunamadı"),
     * @OA\Response(response=422, description="Geçersiz durum bilgisi"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
    // 4. SİPARİŞ DURUMUNU GÜNCELLE (Sadece Admin) - PUT /api/orders/{id}/status
    public function updateStatus(Request $request, $id)
    {
        // Validasyon: Sadece belirli durumlar girilebilir
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:pending,shipped,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Geçersiz durum bilgisi', 'errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Sipariş bulunamadı'], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Sipariş durumu güncellendi',
            'data' => $order
        ], 200);
    }
}