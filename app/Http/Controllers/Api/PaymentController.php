<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Check the status of a payment.
     *
     * GET /api/v1/payments/{id}/status
     *
     * Returns the current payment status so the mobile app can poll
     * after redirecting the user to the Xendit payment page.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil diambil.',
            'data'    => [
                'id'                => $payment->id,
                'payment_type'      => $payment->payment_type,
                'amount'            => $payment->amount,
                'currency'          => $payment->currency,
                'status'            => $payment->status,
                'payment_method'    => $payment->payment_method,
                'xendit_invoice_url' => $payment->xendit_invoice_url,
                'paid_at'           => $payment->paid_at?->toIso8601String(),
                'created_at'        => $payment->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * List authenticated user's payment history.
     *
     * GET /api/v1/payments
     *
     * Query params:
     *   - status: filter by status (pending, completed, failed, refunded)
     *   - payment_type: filter by type (case_payment, subscription, topup)
     *   - per_page: items per page (default 15, max 50)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }

        $perPage = min((int) $request->input('per_page', 15), 50);

        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pembayaran berhasil diambil.',
            'data'    => $payments,
        ]);
    }
}
