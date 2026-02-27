<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Services\AiService;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'data'    => null,
            ], 500);
        }
    }

    // ─── Wallet Top-Up ──────────────────────────────────────────

    /**
     * Top up the authenticated user's wallet.
     *
     * POST /api/rci/topup
     * Body: { "amount": 100000 }
     */
    public function topup(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $transaction = $this->escrowService->topUp(
                $request->user(),
                (float) $request->amount,
            );

            return response()->json([
                'success' => true,
                'message' => 'Wallet topped up successfully.',
                'data'    => $transaction,
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
            'amount'  => 'required|numeric|min:1',
        ]);

        try {
            $case = LegalCase::findOrFail($request->case_id);

            $transaction = $this->escrowService->lockFundsForCase(
                $case,
                (float) $request->amount,
            );

            return response()->json([
                'success' => true,
                'message' => 'Funds locked in escrow successfully.',
                'data'    => $transaction,
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
