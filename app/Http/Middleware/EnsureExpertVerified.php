<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated expert (lawyer/paralegal) has been
 * verified by admin before they can handle cases.
 *
 * Usage in routes:
 *   ->middleware('expert.verified')
 */
class EnsureExpertVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Only apply to expert roles
        if (! $user->isExpert()) {
            return $next($request);
        }

        $profile = $user->expertProfile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil expert belum dibuat. Silakan lengkapi pendaftaran terlebih dahulu.',
            ], 403);
        }

        if ($profile->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen Anda masih dalam proses verifikasi oleh admin. Anda belum dapat mengakses fitur ini.',
                'verification_status' => 'pending',
            ], 403);
        }

        if ($profile->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen Anda ditolak: ' . $profile->rejection_reason . '. Silakan upload ulang dokumen melalui endpoint /auth/resubmit-documents.',
                'verification_status' => 'rejected',
                'rejection_reason'    => $profile->rejection_reason,
            ], 403);
        }

        return $next($request);
    }
}
