<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/products",
     * summary="Ürün Listesi",
     * tags={"Products"},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Ürün adına göre arama",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="category_id",
     * in="query",
     * description="Kategori ID'sine göre filtreleme",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Parameter(
     * name="min_price",
     * in="query",
     * description="Minimum fiyat",
     * required=false,
     * @OA\Schema(type="number")
     * ),
     * @OA\Parameter(
     * name="max_price",
     * in="query",
     * description="Maximum fiyat",
     * required=false,
     * @OA\Schema(type="number")
     * ),
     * @OA\Parameter(
     * name="limit",
     * in="query",
     * description="Sayfa başına kayıt sayısı",
     * required=false,
     * @OA\Schema(type="integer", default=20)
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürünler başarıyla listelendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürünler başarıyla listelendi"),
     * @OA\Property(property="data", type="object")
     * )
     * )
     * )
     */
    // 1. ÜRÜNLERİ LİSTELEME (Filtreleme ve Arama Bonusu Dahil)
    public function index(Request $request)
    {
        // Sorguyu başlatıyoruz
        $query = Product::query();

        // Arama (Ürün ismine göre)
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Kategori Filtresi
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Fiyat Aralığı Filtresi (Min - Max)
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sayfalama (Pagination) - Varsayılan 20 kayıt
        $limit = $request->input('limit', 20);
        $products = $query->with('category')->paginate($limit); // 'with' ile kategori bilgisini de çektik

        return response()->json([
            'success' => true,
            'message' => 'Ürünler başarıyla listelendi',
            'data' => $products
        ], 200);
    }

    /**
     * @OA\Get(
     * path="/api/products/{id}",
     * summary="Ürün Detayı",
     * tags={"Products"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Ürün ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürün detayları getirildi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün detayları getirildi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Ürün bulunamadı")
     * )
     */
    // 2. TEK ÜRÜN GETİRME
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Ürün bulunamadı'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ürün detayları getirildi',
            'data' => $product
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/products",
     * summary="Yeni Ürün Ekleme",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name","price","stock_quantity","category_id"},
     * @OA\Property(property="name", type="string", example="Laptop"),
     * @OA\Property(property="price", type="number", format="float", example=15000.00),
     * @OA\Property(property="stock_quantity", type="integer", example=50),
     * @OA\Property(property="category_id", type="integer", example=1),
     * @OA\Property(property="description", type="string", example="Yüksek performanslı laptop")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Ürün başarıyla eklendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün başarıyla eklendi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=422, description="Validasyon hatası"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
    // 3. YENİ ÜRÜN EKLEME (Sadece Admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id', // Var olan bir kategori ID'si olmalı
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ürün başarıyla eklendi',
            'data' => $product
        ], 201);
    }

    /**
     * @OA\Put(
     * path="/api/products/{id}",
     * summary="Ürün Güncelleme",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Ürün ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Laptop"),
     * @OA\Property(property="price", type="number", format="float", example=15000.00),
     * @OA\Property(property="stock_quantity", type="integer", example=50),
     * @OA\Property(property="category_id", type="integer", example=1),
     * @OA\Property(property="description", type="string", example="Yüksek performanslı laptop")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürün güncellendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün güncellendi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Ürün bulunamadı"),
     * @OA\Response(response=422, description="Validasyon hatası"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
    // 4. ÜRÜN GÜNCELLEME (Sadece Admin)
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Ürün bulunamadı'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'min:3',
            'price' => 'numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'category_id' => 'exists:categories,id',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ürün güncellendi',
            'data' => $product
        ], 200);
    }

    /**
     * @OA\Delete(
     * path="/api/products/{id}",
     * summary="Ürün Silme",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Ürün ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Ürün silindi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Ürün silindi")
     * )
     * ),
     * @OA\Response(response=404, description="Ürün bulunamadı"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
    // 5. ÜRÜN SİLME (Sadece Admin)
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Ürün bulunamadı'], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ürün silindi'
        ], 200);
    }
}