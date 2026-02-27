<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    /**
     * Maximum free questions per day for guests & free users.
     */
    private const FREE_LIMIT = 3;

    /**
     * Cache TTL: seconds remaining until midnight (daily reset).
     */
    private function ttlUntilMidnight(): int
    {
        return (int) now()->diffInSeconds(now()->endOfDay());
    }

    public function __construct(
        private readonly AiService $ai,
    ) {}

    /**
     * POST /api/chat/send
     *
     * Accept a chat message, enforce freemium limits, and return an AI response.
     *
     * @param  Request  $request  {message: string, session_id?: string, user_id?: int}
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message'    => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'user_id'    => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $message = $request->input('message');

        // Resolve authenticated user.
        // Priority: Sanctum auth → manual user_id param (for testing).
        $user = $request->user()
            ?? ($request->filled('user_id')
                ? User::find($request->input('user_id'))
                : null);

        // ── Pro Member → unlimited access ────────────────────────
        if ($user && $user->hasActiveSubscription()) {
            $aiResponse = $this->ai->chat($message, $user);

            return response()->json([
                'status' => 'success',
                'tier'   => 'pro',
                'data'   => $aiResponse,
                'usage'  => [
                    'limit'     => null,
                    'used'      => null,
                    'remaining' => 'unlimited',
                ],
                'escalation' => [
                    'can_escalate' => true,
                    'message'      => 'Sebagai member Pro, Anda dapat terhubung langsung dengan Paralegal/Advokat.',
                    'escalate_url' => url('/api/chat/escalate'),
                ],
            ]);
        }

        // ── Guest / Free User → enforce daily limit ──────────────
        $cacheKey = $this->resolveCacheKey($user, $request->input('session_id'));
        $used     = (int) Cache::get($cacheKey, 0);

        if ($used >= self::FREE_LIMIT) {
            return response()->json([
                'status'  => 'forbidden',
                'tier'    => $user ? 'free' : 'guest',
                'message' => 'Anda telah mencapai batas ' . self::FREE_LIMIT . ' pertanyaan gratis hari ini. '
                           . 'Silakan upgrade ke Pro untuk akses tak terbatas dan konsultasi langsung dengan ahli hukum.',
                'upgrade_url' => url('/pricing'),
                'usage'  => [
                    'limit'     => self::FREE_LIMIT,
                    'used'      => $used,
                    'remaining' => 0,
                ],
            ], 429);
        }

        // Process the message via AI
        $aiResponse = $this->ai->chat($message, $user);

        // Increment usage counter (expires at midnight)
        Cache::put($cacheKey, $used + 1, $this->ttlUntilMidnight());

        return response()->json([
            'status' => 'success',
            'tier'   => $user ? 'free' : 'guest',
            'data'   => $aiResponse,
            'usage'  => [
                'limit'     => self::FREE_LIMIT,
                'used'      => $used + 1,
                'remaining' => self::FREE_LIMIT - ($used + 1),
            ],
            'upgrade_cta' => ($used + 1) >= 2
                ? 'Sisa ' . (self::FREE_LIMIT - ($used + 1)) . ' pertanyaan gratis. Upgrade ke Pro untuk akses unlimited!'
                : null,
        ]);
    }

    /**
     * Build a unique cache key per user or guest session.
     *
     * - Logged-in user  → "ai_chat_usage:user:{id}"
     * - Guest           → "ai_chat_usage:session:{session_id}"
     */
    private function resolveCacheKey(?User $user, ?string $sessionId): string
    {
        if ($user) {
            return "ai_chat_usage:user:{$user->id}";
        }

        // Ensure guests always have a session identifier
        $sessionId = $sessionId ?: Str::uuid()->toString();

        return "ai_chat_usage:session:{$sessionId}";
    }
}
