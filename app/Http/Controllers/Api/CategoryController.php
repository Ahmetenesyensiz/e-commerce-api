<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/categories",
     * summary="Kategorileri Listeleme",
     * tags={"Categories"},
     * @OA\Response(
     * response=200,
     * description="Kategoriler listelendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Kategoriler listelendi"),
     * @OA\Property(property="data", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'message' => 'Kategoriler listelendi',
            'data' => $categories
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/categories",
     * summary="Yeni Kategori Ekleme",
     * tags={"Categories"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name"},
     * @OA\Property(property="name", type="string", example="Elektronik"),
     * @OA\Property(property="description", type="string", example="Elektronik ürünler kategorisi")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Kategori başarıyla oluşturuldu",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Kategori başarıyla oluşturuldu"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=422, description="Validasyon hatası"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
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

    /**
     * @OA\Put(
     * path="/api/categories/{id}",
     * summary="Kategori Güncelleme",
     * tags={"Categories"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Kategori ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name"},
     * @OA\Property(property="name", type="string", example="Elektronik"),
     * @OA\Property(property="description", type="string", example="Elektronik ürünler kategorisi")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Kategori güncellendi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Kategori güncellendi"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="Kategori bulunamadı"),
     * @OA\Response(response=422, description="Validasyon hatası"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
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

    /**
     * @OA\Delete(
     * path="/api/categories/{id}",
     * summary="Kategori Silme",
     * tags={"Categories"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Kategori ID",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Kategori silindi",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Kategori silindi")
     * )
     * ),
     * @OA\Response(response=404, description="Kategori bulunamadı"),
     * @OA\Response(response=401, description="Yetkisiz erişim"),
     * @OA\Response(response=403, description="Admin yetkisi gerekli")
     * )
     */
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