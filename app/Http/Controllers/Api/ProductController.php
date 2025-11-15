<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
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