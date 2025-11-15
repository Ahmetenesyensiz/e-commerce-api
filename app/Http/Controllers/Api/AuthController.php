<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // 1. KULLANICI KAYDI (POST /api/register)
    public function register(Request $request)
    {
        // PDF'teki Validasyon Kuralları
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2',       // [cite: 80]
            'email' => 'required|email|unique:users', // [cite: 81]
            'password' => 'required|min:8',   // [cite: 82]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user' 
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Kullanıcı başarıyla oluşturuldu',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201);
    }

    // 2. KULLANICI GİRİŞİ (POST /api/login)
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Giriş bilgileri hatalı',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Giriş başarılı',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    // 3. PROFİL GÖRÜNTÜLEME (GET /api/profile)
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil bilgileri getirildi',
            'data' => $request->user()
        ], 200);
    }

    // 4. PROFİL GÜNCELLEME (PUT /api/profile) -> EKSİK OLAN BUYDU
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Güncelleme için validasyon (Email değişirse unique olmalı ama kendi maili hariç)
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422);
        }

        // Sadece gelen verileri güncelle
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil başarıyla güncellendi',
            'data' => $user
        ], 200);
    }

    // ÇIKIŞ (LOGOUT)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Çıkış yapıldı'], 200);
    }
}