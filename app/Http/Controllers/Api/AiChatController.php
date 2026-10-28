<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiQuotaService;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function __construct(
        private readonly AiService $ai,
        private readonly AiQuotaService $quota,
    ) {}

    /**
     * POST /api/chat/send
     *
     * Resolve the Sanctum user even though this route has no auth middleware,
     * then consume the daily quota before calling any AI provider.
     *
     * @param  Request  $request  {message: string}
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = $request->input('message');
        $user = $request->user('sanctum');

        try {
            $quota = $this->quota->consume($user);
        } catch (\Throwable $e) {
            Log::error('AiChatController quota error: ' . $e->getMessage());

            return $this->serviceUnavailable();
        }

        if (! $quota['allowed']) {
            return $this->denied($quota);
        }

        try {
            $answer = $this->ai->chat($message, $user);
        } catch (\Throwable $e) {
            Log::error('AiChatController send error: ' . $e->getMessage());

            return $this->serviceUnavailable($this->refundUsage($quota));
        }

        if ($answer['fallback'] ?? false) {
            return $this->serviceUnavailable($this->refundUsage($quota));
        }

        unset($answer['fallback']);

        return response()->json([
            'status' => 'success',
            'tier'   => 'pro',
            'data'   => $answer,
            'usage'  => [
                'limit'     => $quota['limit'],
                'used'      => $quota['used'],
                'remaining' => $quota['remaining'],
            ],
            'escalation' => [
                'can_escalate' => true,
                'message'      => 'Sebagai member Pro, Anda dapat terhubung langsung dengan Paralegal/Advokat.',
                'escalate_url' => url('/api/chat/escalate'),
            ],
        ]);
    }

    /**
     * @param  array{allowed: bool, reason: string|null, limit: int, used: int, remaining: int, key: string|null}  $quota
     */
    private function denied(array $quota): JsonResponse
    {
        $exhausted = $quota['reason'] === 'quota_exhausted';

        $message = match ($quota['reason']) {
            'guest'           => 'Silakan login dan aktifkan membership untuk menggunakan AI Assistant.',
            'not_member'      => 'AI Assistant hanya tersedia untuk member dengan subscription aktif.',
            default           => 'Anda telah mencapai batas ' . $quota['limit'] . ' pertanyaan AI hari ini. Kuota diperbarui pukul 00.00 WIB.',
        };

        return response()->json([
            'status'  => 'forbidden',
            'tier'    => match ($quota['reason']) {
                'guest'      => 'guest',
                'not_member' => 'free',
                default      => 'pro',
            },
            'message' => $message,
            'usage'   => [
                'limit'     => $quota['limit'],
                'used'      => $quota['used'],
                'remaining' => $quota['remaining'],
            ],
        ], $exhausted ? 429 : 403);
    }

    /**
     * @param  array{limit: int, used: int, remaining: int, key: string|null}  $quota
     * @return array{limit: int, used: int, remaining: int}|null
     */
    private function refundUsage(array $quota): ?array
    {
        try {
            return $this->quota->refund($quota);
        } catch (\Throwable $e) {
            Log::error('AiChatController refund error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param  array{limit: int, used: int, remaining: int}|null  $usage
     */
    private function serviceUnavailable(?array $usage = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi sesaat lagi.',
            'usage'   => $usage,
        ], 503);
    }
}
