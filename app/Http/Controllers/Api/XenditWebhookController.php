<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __construct(
        protected XenditService $xenditService,
    ) {}

    /**
     * Handle Xendit Invoice webhook callback.
     *
     * POST /api/v1/xendit/webhook/invoice
     *
     * Xendit sends this when an invoice status changes (e.g., PAID, EXPIRED).
     * We verify the callback token, then process accordingly.
     */
    public function handleInvoiceCallback(Request $request): JsonResponse
    {
        // 1. Verify webhook authenticity
        $callbackToken = $request->header('x-callback-token', '');

        if (! $this->xenditService->verifyWebhookToken($callbackToken)) {
            Log::warning('Xendit webhook: invalid callback token', [
                'ip'    => $request->ip(),
                'token' => substr($callbackToken, 0, 8) . '***',
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Parse the webhook payload
        $payload = $request->all();

        Log::info('Xendit webhook received', [
            'id'          => $payload['id'] ?? null,
            'external_id' => $payload['external_id'] ?? null,
            'status'      => $payload['status'] ?? null,
        ]);

        $xenditInvoiceId = $payload['id'] ?? null;
        $status          = $payload['status'] ?? null;
        $externalId      = $payload['external_id'] ?? null;

        if (! $xenditInvoiceId || ! $status) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // 3. Find the matching payment record
        $payment = Payment::where('xendit_invoice_id', $xenditInvoiceId)->first();

        if (! $payment) {
            Log::warning('Xendit webhook: payment not found', [
                'xendit_invoice_id' => $xenditInvoiceId,
                'external_id'      => $externalId,
            ]);

            // Return 200 to prevent Xendit from retrying
            return response()->json(['message' => 'Payment not found, acknowledged'], 200);
        }

        // 4. Idempotency check — skip if already processed
        if ($payment->status !== 'pending') {
            Log::info('Xendit webhook: payment already processed', [
                'payment_id' => $payment->id,
                'status'     => $payment->status,
            ]);

            return response()->json(['message' => 'Already processed'], 200);
        }

        // 5. Process based on status
        return match (strtoupper($status)) {
            'PAID', 'SETTLED' => $this->handlePaid($payment, $payload),
            'EXPIRED'         => $this->handleExpired($payment, $payload),
            default           => response()->json(['message' => 'Status acknowledged'], 200),
        };
    }

    /**
     * Handle a successfully paid invoice.
     *
     * Credits the user's wallet and updates the payment record.
     */
    private function handlePaid(Payment $payment, array $payload): JsonResponse
    {
        try {
            DB::transaction(function () use ($payment, $payload) {
                // Lock the payment row to prevent race conditions
                $payment = Payment::where('id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                // Double-check idempotency inside transaction
                if ($payment->status !== 'pending') {
                    return;
                }

                // Update payment status
                $payment->update([
                    'status'         => 'completed',
                    'payment_method' => $payload['payment_method'] ?? $payload['payment_channel'] ?? null,
                    'paid_at'        => now(),
                    'metadata'       => array_merge($payment->metadata ?? [], [
                        'xendit_paid_amount'    => $payload['paid_amount'] ?? null,
                        'xendit_payment_method' => $payload['payment_method'] ?? null,
                        'xendit_payment_channel' => $payload['payment_channel'] ?? null,
                        'xendit_bank_code'      => $payload['bank_code'] ?? null,
                    ]),
                ]);

                // Credit the user's wallet
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $payment->user_id],
                    ['balance' => '0.00'],
                );

                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                $amountStr = number_format((float) $payment->amount, 2, '.', '');
                $wallet->credit($amountStr);

                // Record wallet transaction
                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'amount'         => $amountStr,
                    'type'           => 'deposit',
                    'reference_id'   => $payment->id,
                    'reference_type' => Payment::class,
                    'status'         => 'success',
                    'description'    => "Top-up via Xendit sebesar Rp " . number_format((float) $amountStr, 0, ',', '.'),
                ]);
            });

            Log::info('Xendit webhook: payment completed', [
                'payment_id' => $payment->id,
                'user_id'    => $payment->user_id,
                'amount'     => $payment->amount,
            ]);

            return response()->json(['message' => 'Payment processed successfully'], 200);

        } catch (\Throwable $e) {
            Log::error('Xendit webhook: processing failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            // Return 500 so Xendit will retry
            return response()->json(['message' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle an expired invoice.
     *
     * Marks the payment as failed.
     */
    private function handleExpired(Payment $payment, array $payload): JsonResponse
    {
        $payment->update([
            'status'   => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'failure_reason' => 'Invoice expired',
                'expired_at'     => $payload['updated'] ?? now()->toIso8601String(),
            ]),
        ]);

        Log::info('Xendit webhook: invoice expired', [
            'payment_id' => $payment->id,
        ]);

        return response()->json(['message' => 'Expiry processed'], 200);
    }
}
