<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login user
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // Cek apakah user ada dan password bener
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        // Bikin token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        // Buat activity
        Activity::create([
            'user_id' => $user->id,
            'activity' => 'login ',
            'detail' => 'Berhasil Login',
            'type' => 'system',
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
            ],

        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'datta berhasil dimabil ',
            'data' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // catat aktivitas dulu
        Activity::create([
            'user_id' => $user->id,
            'activity' => 'Logout',
            'detail' => 'Berhasil logout',
            'type' => 'system',
            'date' => now(),
        ]);

        // hapus token yang lagi dipakai (logout dari device ini saja)
        $user->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'logout berhasil',
        ]);
    }
}
