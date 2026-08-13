<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateExpertProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ExpertProfileResource;
use App\Http\Resources\UserResource;
use App\Models\ExpertProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // ─── Get Full Profile ───────────────────────────────────────

    /**
     * Retrieve the authenticated user's complete profile,
     * including expert profile and wallet info if applicable.
     *
     * GET /api/v1/profile
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        // Eager load relevant relations based on role
        $relations = [];
        if ($user->isExpert()) {
            $relations[] = 'expertProfile';
        }
        $relations[] = 'wallet';

        $user->load($relations);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil.',
            'data'    => new UserResource($user),
        ]);
    }

    // ─── Update Profile ─────────────────────────────────────────

    /**
     * Update the authenticated user's basic profile fields
     * (name, phone, avatar).
     *
     * PUT /api/v1/profile
     *
     * Accepts multipart/form-data (when avatar is included)
     * or application/json (for name/phone only).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->input('name');
        }

        if ($request->has('phone')) {
            $updateData['phone'] = $request->input('phone');
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $updateData['avatar_url'] = $this->uploadAvatar($request, $user);
        }

        if (empty($updateData)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang diperbarui. Kirim minimal satu field (name, phone, atau avatar).',
            ], 422);
        }

        $user->update($updateData);

        // Reload to reflect changes + include relations
        $user->load($user->isExpert() ? ['expertProfile', 'wallet'] : ['wallet']);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => new UserResource($user),
        ]);
    }

    // ─── Change Password ────────────────────────────────────────

    /**
     * Change the authenticated user's password.
     * Requires the current password for verification.
     *
     * PUT /api/v1/profile/password
     *
     * Note: Users who signed up via Google OAuth (password is null)
     * cannot change password here — they must use forgot-password flow first.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Block Google OAuth-only users who don't have a password set
        // This check MUST run before validation, because 'current_password' rule
        // would fail with a misleading error on null passwords.
        if (is_null($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda terdaftar melalui Google. Gunakan fitur "Lupa Password" untuk mengatur password terlebih dahulu.',
            ], 400);
        }

        $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password'  => 'Password saat ini tidak cocok.',
            'password.required'                  => 'Password baru wajib diisi.',
            'password.min'                       => 'Password baru minimal 8 karakter.',
            'password.confirmed'                 => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Revoke all existing tokens except the current one,
        // so other devices are logged out but current session stays alive
        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Sesi perangkat lain telah dikeluarkan.',
        ]);
    }

    // ─── Update Expert Profile ──────────────────────────────────

    /**
     * Update the authenticated expert's professional profile
     * (bio, specialization_tags, experience_years).
     *
     * PUT /api/v1/profile/expert
     *
     * Only accessible by paralegal and lawyer roles.
     */
    public function updateExpertProfile(UpdateExpertProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->expertProfile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil expert tidak ditemukan. Hubungi admin jika Anda yakin sudah terdaftar sebagai expert.',
            ], 404);
        }

        $updateData = $request->only(['bio', 'specialization_tags', 'experience_years']);

        if (empty($updateData)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang diperbarui. Kirim minimal satu field (bio, specialization_tags, atau experience_years).',
            ], 422);
        }

        $profile->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profil expert berhasil diperbarui.',
            'data'    => new ExpertProfileResource($profile->fresh()),
        ]);
    }

    // ─── Delete Avatar ──────────────────────────────────────────

    /**
     * Remove the authenticated user's avatar, resetting it to null.
     *
     * DELETE /api/v1/profile/avatar
     */
    public function deleteAvatar(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->avatar_url) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki avatar.',
            ], 404);
        }

        // Delete old file from storage if it's a local upload (not an external URL like Google avatar)
        $this->deleteOldAvatar($user->avatar_url);

        $user->update(['avatar_url' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil dihapus.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Upload avatar to public disk and return the accessible URL.
     * Deletes the old avatar file if it was a local upload.
     */
    private function uploadAvatar(UpdateProfileRequest $request, $user): string
    {
        // Delete old avatar if exists and is a local upload
        if ($user->avatar_url) {
            $this->deleteOldAvatar($user->avatar_url);
        }

        $path = $request->file('avatar')
            ->store("avatars/{$user->id}", 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an old avatar from public storage.
     * Only deletes if the URL points to our local storage (not external URLs like Google).
     */
    private function deleteOldAvatar(?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        // Only delete local uploads (URLs containing /storage/avatars/)
        if (str_contains($avatarUrl, '/storage/avatars/')) {
            // Extract relative path from full URL
            $relativePath = str_replace(Storage::disk('public')->url(''), '', $avatarUrl);
            
            if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }
}
