<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // 1. LİSTELEME (Herkese Açık)
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'message' => 'Kategoriler listelendi',
            'data' => $categories
        ], 200);
    }

    // 2. EKLEME (Sadece Admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori başarıyla oluşturuldu',
            'data' => $category
        ], 201);
    }

    // 3. GÜNCELLEME (Sadece Admin)
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Kategori bulunamadı'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,'.$id,
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori güncellendi',
            'data' => $category
        ], 200);
    }

    // 4. SİLME (Sadece Admin)
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Kategori bulunamadı'], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori silindi'
        ], 200);
    }
}