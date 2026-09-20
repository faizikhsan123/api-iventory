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
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
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
        $request->user()->currentAccessToken()->delete();

        $user = $request->user();

        // Buat activity
        Activity::create([
            'user_id' => $user->id,
            'activity' => ' logout ',
            'detail' => 'Berhasil logout',
            'type' => 'system',
            'date' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'logout berhasil',
        ]);
    }
}
