<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contracts\UserRepositoryInterface;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
                'message' => 'User fetched successfully!',
                'data' => $user->load('assets'),
            ]);
    }

    public function register(RegisterRequest $request)
    {
        if ($request->validated()) {
            $user = User::create([
                'name' => $request->email,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'balance' => 10 // assign free $10 by default
            ]);

            return response()->json([
                'message' => 'User registered successfully!',
                'data' => $user
            ], 201);
        }

        return response()->json([
                'message' => 'Something is wrong!'
            ], 401);
    }

    public function login(LoginRequest $request)
    {
        if ($request->validated() && Auth::attempt($request->all())) {

            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'User logged in successfully!',
                'data' => [
                    'token' => $token
                ],
            ], 200);
        }

        return response()->json([
                'message' => 'Something is wrong!'
            ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully!',
        ]);
    }
}
