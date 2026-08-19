<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Models\LegalCase;
use App\Services\AiService;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class RciApiController extends Controller
{
    public function __construct(
        protected AiService $aiService,
        protected EscrowService $escrowService,
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

        try {
            $result = $this->aiService->chat(
                $request->message,
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'AI response generated successfully.',
                'data'    => $result,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RciApiController chat error: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI response generated via fallback.',
                'data'    => [
                    'answer'     => 'Terima kasih atas pertanyaan Anda. Permasalahan yang Anda sampaikan berkaitan dengan ranah hukum di Indonesia. Silakan berikan rincian kronologi atau konsultasikan langsung dengan Paralegal kami.',
                    'topic'      => 'umum',
                    'confidence' => 0.50,
                    'disclaimer' => 'Jawaban bersifat umum. Hubungi Paralegal kami untuk bantuan langkah teknis.',
                ],
            ]);
        }
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
     * Upgrade the authenticated user's membership to Pro.
     *
     * POST /api/rci/upgrade
     */
    public function upgrade(Request $request): JsonResponse
    {
        try {
            $this->escrowService->subscribePro($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Membership upgraded to Pro successfully.',
                'data'    => null,
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
