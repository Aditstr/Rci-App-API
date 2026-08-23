<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            // A suspended account must lose every active device/session immediately.
            $user->tokens()->delete();

            return new JsonResponse([
                'success' => false,
                'code' => 'ACCOUNT_SUSPENDED',
                'message' => 'Akun Anda dinonaktifkan karena pemeriksaan kepatuhan. Silakan hubungi admin RCI.',
            ], 403);
        }

        return $next($request);
    }
}
