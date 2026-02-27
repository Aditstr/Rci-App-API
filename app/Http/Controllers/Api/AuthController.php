<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    // ─── Register ───────────────────────────────────────────────

    /**
     * Register a new user, create their wallet, and return a Sanctum token.
     *
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|in:client,paralegal,lawyer,corporate',
        ]);

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
            ]);

            // Create a wallet with 0 balance for the new user
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            event(new Registered($user));

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user'    => $user,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'token'   => null,
                'user'    => null,
            ], 500);
        }
    }

    // ─── Login ──────────────────────────────────────────────────

    /**
     * Authenticate a user and return a Sanctum token.
     *
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Your email address is not verified. Please check your inbox for the verification link.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // ─── Logout ─────────────────────────────────────────────────

    /**
     * Revoke the current access token.
     *
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // ─── Me (Profile) ───────────────────────────────────────────

    /**
     * Return the authenticated user's data and wallet balance.
     *
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = $user->wallet;

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved.',
            'user'    => $user,
            'wallet'  => [
                'balance' => $wallet?->balance ?? '0.00',
            ],
        ]);
    }
}
