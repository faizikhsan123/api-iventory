<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    // Register user baru
    public function register(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|min:3|max:20',
            'email' => 'required|email|max:50|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Bikin user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        // Bikin token untuk user ini
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return response sukses
        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dibuat',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ], 201);
    }

    // Login user
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email|max:50|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // Cek apakah user ada dan password bener
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        // Bikin token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ]
        ]);
    }
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'datta berhasil dimabil ',
            'data' => new UserResource($request->user())
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'logout berhasil'
        ]);
    }
}
