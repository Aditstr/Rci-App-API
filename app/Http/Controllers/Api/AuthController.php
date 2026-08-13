<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpertProfileResource;
use App\Http\Resources\UserResource;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ─── Register ───────────────────────────────────────────────

    /**
     * Register a new user and return a Sanctum token.
     *
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
            ]);

            // Auto-create wallet for new user (AUTH-08)
            \App\Models\Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // ── Expert document upload (lawyer / paralegal) ─────
            if (in_array($request->role, ['lawyer', 'paralegal'])) {
                $docPaths = $this->uploadExpertDocuments($request, $user);

                ExpertProfile::create(array_merge([
                    'user_id'             => $user->id,
                    'license_number'      => 'PENDING-' . \Illuminate\Support\Str::uuid()->toString(), // to be filled after verification
                    'verification_status' => 'pending',
                ], $docPaths));
            }

            event(new Registered($user));

            // Generate token upon registration (AUTH-01)
            $token = $user->createToken('auth-token')->plainTextToken;

            $message = 'Registration successful. Please check your email to verify your account.';
            if (in_array($request->role, ['lawyer', 'paralegal'])) {
                $message = 'Registrasi berhasil. Silakan verifikasi email Anda. Dokumen Anda akan ditinjau oleh admin sebelum Anda dapat menangani kasus.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'token'   => $token,
                'user'    => new UserResource($user->load('expertProfile')),
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Registration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal. Silakan coba lagi nanti.',
                'token'   => null,
                'user'    => null,
            ], 500);
        }
    }

    /**
     * Upload expert documents and return an array of storage paths.
     */
    private function uploadExpertDocuments(RegisterRequest $request, User $user): array
    {
        $basePath = "expert-documents/{$user->id}";
        $paths = [];

        // KTP — wajib untuk paralegal & lawyer
        if ($request->hasFile('ktp')) {
            $paths['ktp_path'] = $request->file('ktp')
                ->store("{$basePath}/ktp", 'local');
        }

        // Ijazah — wajib untuk paralegal & lawyer
        if ($request->hasFile('ijazah')) {
            $paths['ijazah_path'] = $request->file('ijazah')
                ->store("{$basePath}/ijazah", 'local');
        }

        // License Card (PERADI) — wajib untuk lawyer
        if ($request->hasFile('license_card')) {
            $paths['license_card_path'] = $request->file('license_card')
                ->store("{$basePath}/license", 'local');
        }

        // Selfie — wajib untuk lawyer
        if ($request->hasFile('selfie')) {
            $paths['selfie_path'] = $request->file('selfie')
                ->store("{$basePath}/selfie", 'local');
        }

        // CV — opsional untuk lawyer
        if ($request->hasFile('cv')) {
            $paths['cv_path'] = $request->file('cv')
                ->store("{$basePath}/cv", 'local');
        }

        return $paths;
    }

    // ─── Login ──────────────────────────────────────────────────

    /**
     * Authenticate a user and return a Sanctum token.
     *
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check active status (AUTH-09)
        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.',
            ], 403);
        }

        // Eager load expertProfile to avoid N+1 lazy loading during verification checks
        if ($user->isExpert()) {
            $user->load('expertProfile');
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Your email address is not verified. Please check your inbox for the verification link.',
            ], 403);
        }

        // Revoke previous tokens on new login to prevent token accumulation (AUTH-04)
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        // ── Include verification status for experts ─────────
        $responseData = [
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ];

        if ($user->isExpert() && $user->expertProfile) {
            $responseData['expert_verification'] = [
                'status'           => $user->expertProfile->verification_status,
                'rejection_reason' => $user->expertProfile->rejection_reason,
                'can_handle_cases' => $user->expertProfile->isApproved(),
            ];

            if ($user->expertProfile->isPending()) {
                $responseData['message'] = 'Login berhasil. Dokumen Anda masih dalam proses verifikasi oleh admin. Anda belum dapat menangani kasus.';
            } elseif ($user->expertProfile->isRejected()) {
                $responseData['message'] = 'Login berhasil. Dokumen Anda ditolak: ' . $user->expertProfile->rejection_reason . '. Silakan upload ulang dokumen Anda.';
            }
        }

        return response()->json($responseData);
    }

    // ─── Google OAuth ───────────────────────────────────────────

    /**
     * Redirect the user to the Google authentication page.
     *
     * GET /api/v1/auth/google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle callback from Google.
     *
     * GET /api/v1/auth/google/callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Register new user as client by default
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'role' => 'client',
                    'email_verified_at' => now(),
                    'is_verified' => true,
                    // password remains null
                ]);

                // Auto-create wallet
                \App\Models\Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0]
                );
                
                event(new Registered($user));
            } else {
                // Update existing user with google id if not set
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar_url' => $user->avatar_url ?? $googleUser->getAvatar(),
                    ]);
                }
            }

            // Revoke old tokens
            $user->tokens()->delete();

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;
            // Removed encoding of user data in URL as the frontend fetches it via /auth/me

            // Redirect back to frontend URL defined in .env
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/auth/google/callback#token={$token}");

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google Auth Error: ' . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/auth/google/callback#error=google_auth_failed");
        }
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
     * Return the authenticated user's data.
     *
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = ['user' => new UserResource($user)];

        if ($user->isExpert()) {
            $user->load('expertProfile');
            $data['expert_verification'] = $user->expertProfile ? [
                'status'           => $user->expertProfile->verification_status,
                'can_handle_cases' => $user->expertProfile->isApproved(),
            ] : null;
        }

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved.',
            ...$data,
        ]);
    }

    // ─── Re-submit Documents (After Rejection) ─────────────────

    /**
     * Allow rejected experts to re-upload their documents.
     *
     * POST /api/v1/auth/resubmit-documents
     */
    public function resubmitDocuments(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isExpert()) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini hanya tersedia untuk Lawyer dan Paralegal.',
            ], 403);
        }

        $profile = $user->expertProfile;

        if (! $profile || ! $profile->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat mengunggah ulang dokumen jika pendaftaran sebelumnya ditolak.',
            ], 400);
        }

        // Validate based on role
        $rules = [
            'ktp'    => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'ijazah' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];

        if ($user->isLawyer()) {
            $rules['license_card'] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
            $rules['selfie']       = ['required', 'file', 'mimes:jpg,jpeg,png',     'max:5120'];
            $rules['cv']           = ['nullable', 'file', 'mimes:pdf,doc,docx',      'max:10240'];
        }

        $request->validate($rules);

        $basePath = "expert-documents/{$user->id}";
        $updateData = [
            'verification_status' => 'pending',
            'rejection_reason'    => null,
            'verified_at'         => null,
        ];

        // Store new files first (AUTH-10)
        if ($request->hasFile('ktp')) {
            $updateData['ktp_path'] = $request->file('ktp')->store("{$basePath}/ktp", 'local');
        }
        if ($request->hasFile('ijazah')) {
            $updateData['ijazah_path'] = $request->file('ijazah')->store("{$basePath}/ijazah", 'local');
        }
        if ($request->hasFile('license_card')) {
            $updateData['license_card_path'] = $request->file('license_card')->store("{$basePath}/license", 'local');
        }
        if ($request->hasFile('selfie')) {
            $updateData['selfie_path'] = $request->file('selfie')->store("{$basePath}/selfie", 'local');
        }
        if ($request->hasFile('cv')) {
            $updateData['cv_path'] = $request->file('cv')->store("{$basePath}/cv", 'local');
        }

        // Old paths to delete after success
        $oldPaths = array_filter([
            $profile->ktp_path,
            $profile->ijazah_path,
            $profile->license_card_path,
            $profile->cv_path,
            $profile->selfie_path,
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($profile, $updateData) {
            $profile->update($updateData);
        });

        // Delete old files safely after DB commit
        foreach ($oldPaths as $path) {
            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah ulang. Tim admin akan meninjau kembali pendaftaran Anda.',
            'data'    => $profile->fresh(),
        ]);
    }

    // ─── Forgot Password ────────────────────────────────────────

    /**
     * Mengirimkan link reset password (berisi token) ke email pengguna.
     *
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        \Illuminate\Support\Facades\Password::broker()->sendResetLink(
            $request->only('email')
        );

        // Security: Always return generic success response to prevent user enumeration (AUTH-06)
        return response()->json([
            'success' => true,
            'message' => 'Jika email Anda terdaftar, kami telah mengirimkan link reset password ke email Anda.',
        ]);
    }

    // ─── Reset Password ─────────────────────────────────────────

    /**
     * Memverifikasi token dan mengubah password pengguna.
     *
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = \Illuminate\Support\Facades\Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(\Illuminate\Support\Str::random(60));

                $user->save();

                // Security: Revoke all active tokens upon password reset (AUTH-03)
                $user->tokens()->delete();

                event(new \Illuminate\Auth\Events\PasswordReset($user));
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => __($status),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }
}
