<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Mark the user's email address as verified.
     *
     * Link dibuka dari inbox (browser) sehingga selalu redirect ke frontend,
     * bukan JSON. Status dibawa via query param:
     * ?verified=1 | ?verified=already | ?verify=invalid
     *
     * GET /api/v1/email/verify/{id}/{hash}
     */
    public function verify(Request $request, $id, $hash): RedirectResponse|JsonResponse
    {
        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $user = User::find($id);

        if (! $user || ! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            // ponytail: API client yang minta JSON tetap dapat JSON
            if ($request->expectsJson() && ! $request->isMethod('get')) {
                return response()->json(['success' => false, 'message' => 'Invalid verification link.'], 403);
            }

            return redirect("{$frontend}/login?verify=invalid");
        }

        if ($user->hasVerifiedEmail()) {
            return redirect("{$frontend}/login?verified=already");
        }

        if ($user->markEmailAsVerified()) {
            User::where('id', $user->id)->update(['is_verified' => \Illuminate\Support\Facades\DB::raw('true')]);
        }

        return redirect("{$frontend}/login?verified=1");
    }

    /**
     * Resend the email verification notification.
     *
     * POST /api/email/resend
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Security: Always return success to prevent user enumeration
        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar dan belum terverifikasi, link verifikasi telah dikirim.',
        ], 200);
    }
}
