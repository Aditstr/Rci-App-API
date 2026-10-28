<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Models\LegalCase;
use App\Services\AiQuotaService;
use App\Services\AiService;
use App\Services\EscrowService;
use App\Services\MembershipConfig;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RciApiController extends Controller
{
    public function __construct(
        protected AiService $aiService,
        protected EscrowService $escrowService,
        protected SubscriptionService $subscriptionService,
        protected AiQuotaService $aiQuota,
    ) {}

    // ─── AI Chat ────────────────────────────────────────────────

    /**
     * Send a message to the AI assistant.
     *
     * POST /api/rci/chat
     * Body: { "message": "string" }
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        // Consume before calling the provider so parallel requests cannot pass the limit.
        try {
            $quota = $this->aiQuota->consume($user);
        } catch (\Throwable $e) {
            Log::error('RciApiController quota error: ' . $e->getMessage());

            return $this->chatServiceUnavailable();
        }

        if (! $quota['allowed']) {
            return $this->chatDenied($quota);
        }

        try {
            $result = $this->aiService->chat(
                $request->message,
                $user,
            );
        } catch (RuntimeException $e) {
            if ($this->refundUsage($quota) === null) {
                return $this->chatServiceUnavailable();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        } catch (\Throwable $e) {
            Log::error('RciApiController chat error: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->chatServiceUnavailable($this->refundUsage($quota));
        }

        if ($result['fallback'] ?? false) {
            return $this->chatServiceUnavailable($this->refundUsage($quota));
        }

        unset($result['fallback']);

        return response()->json([
            'success' => true,
            'message' => 'AI response generated successfully.',
            'tier'    => 'pro',
            'data'    => $result,
            'usage'   => [
                'limit'     => $quota['limit'],
                'used'      => $quota['used'],
                'remaining' => $quota['remaining'],
            ],
        ]);
    }

    /**
     * @param  array{allowed: bool, reason: string|null, limit: int, used: int, remaining: int, key: string|null}  $quota
     */
    private function chatDenied(array $quota): JsonResponse
    {
        $exhausted = $quota['reason'] === 'quota_exhausted';

        return response()->json([
            'success' => false,
            'tier'    => $exhausted ? 'pro' : 'free',
            'message' => $exhausted
                ? 'Anda telah mencapai batas ' . $quota['limit'] . ' pertanyaan AI hari ini. Kuota diperbarui pukul 00.00 WIB.'
                : 'AI Assistant hanya tersedia untuk member dengan subscription aktif.',
            'data'    => null,
            'usage'   => [
                'limit'     => $quota['limit'],
                'used'      => $quota['used'],
                'remaining' => $quota['remaining'],
            ],
        ], $exhausted ? 429 : 403);
    }

    /**
     * @param  array{allowed: bool, reason: string|null, limit: int, used: int, remaining: int, key: string|null}  $quota
     * @return array{limit: int, used: int, remaining: int}|null
     */
    private function refundUsage(array $quota): ?array
    {
        try {
            return $this->aiQuota->refund($quota);
        } catch (\Throwable $e) {
            Log::error('RciApiController refund error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param  array{limit: int, used: int, remaining: int}|null  $usage
     */
    private function chatServiceUnavailable(?array $usage = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Layanan AI sedang tidak tersedia. Silakan coba lagi sesaat lagi.',
            'data'    => null,
            'usage'   => $usage,
        ], 503);
    }

    /**
     * Top up the authenticated user's wallet via Xendit payment.
     *
     * POST /api/rci/topup
     * Body: { "amount": 100000 }
     *
     * Returns a Xendit Invoice URL that the user should open
     * to complete the payment. Wallet will be credited automatically
     * via webhook when payment is confirmed.
     */
    public function topup(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:100000000',
        ]);

        try {
            $payment = $this->escrowService->topUp(
                $request->user(),
                (float) $request->amount,
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice pembayaran berhasil dibuat. Silakan selesaikan pembayaran.',
                'data'    => [
                    'payment_id'        => $payment->id,
                    'amount'            => $payment->amount,
                    'currency'          => $payment->currency,
                    'status'            => $payment->status,
                    'invoice_url'       => $payment->xendit_invoice_url,
                    'xendit_invoice_id' => $payment->xendit_invoice_id,
                    'expiry_date'       => $payment->xendit_expiry_date?->toIso8601String(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat invoice pembayaran. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    // ─── Upgrade to Pro ─────────────────────────────────────────

    /**
     * Read-only membership details for the authenticated user.
     *
     * GET /api/rci/membership
     */
    public function membership(Request $request): JsonResponse
    {
        $user = $request->user();
        $config = MembershipConfig::all();
        $status = $this->subscriptionService->getStatus($user);

        return response()->json([
            'success' => true,
            'message' => 'Informasi membership berhasil diambil.',
            'data'    => [
                'config' => [
                    'price'          => $config['price'],
                    'duration_days'  => $config['duration_days'],
                    'ai_daily_limit' => $config['ai_daily_limit'],
                    'enabled'        => $config['enabled'],
                ],
                'status' => [
                    'is_pro'    => $status['is_pro'],
                    'plan'      => $status['plan'],
                    'starts_at' => $status['starts_at'] ?? null,
                    'ends_at'   => $status['ends_at'] ?? null,
                    'days_left' => $status['days_left'] ?? null,
                ],
                'usage'        => $this->aiQuota->snapshot($user),
                'can_purchase' => $user->role === 'client',
            ],
        ]);
    }

    /**
     * Buy or extend the authenticated user's membership.
     *
     * POST /api/rci/upgrade
     */
    public function upgrade(Request $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->purchase(
                $request->user(),
                $request->header('Idempotency-Key'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Membership berhasil diaktifkan.',
                'data'    => [
                    'subscription_id' => $subscription->id,
                    'price'           => $subscription->price,
                    'starts_at'       => $subscription->starts_at->toIso8601String(),
                    'ends_at'         => $subscription->ends_at->toIso8601String(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'data'    => null,
            ], 500);
        }
    }

    // ─── Escrow / Start Case ────────────────────────────────────

    /**
     * Lock funds in escrow for a legal case.
     *
     * POST /api/rci/escrow/start
     * Body: { "case_id": 1, "amount": 500000 }
     */
    public function startCase(Request $request): JsonResponse
    {
        $request->validate([
            'case_id' => 'required|integer|exists:legal_cases,id',
            'amount'  => 'required|numeric|min:1|max:100000000',
        ]);

        try {
            $case = LegalCase::findOrFail($request->case_id);

            // Security: Ensure the authenticated user is the owner of this case
            Gate::authorize('view', $case);

            $transaction = $this->escrowService->lockFundsForCase(
                $case,
                (float) $request->amount,
            );

            return response()->json([
                'success' => true,
                'message' => 'Funds locked in escrow successfully.',
                'data'    => new WalletTransactionResource($transaction),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'data'    => null,
            ], 500);
        }
    }
}
