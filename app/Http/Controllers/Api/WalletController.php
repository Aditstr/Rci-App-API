<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get the authenticated user's wallet balance and summary.
     *
     * GET /api/v1/wallet
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return response()->json([
                'success' => true,
                'message' => 'Wallet belum dibuat. Silakan lakukan top-up pertama Anda.',
                'data'    => [
                    'balance'             => '0.00',
                    'total_income'        => '0.00',
                    'total_spent'         => '0.00',
                    'pending_escrow'      => '0.00',
                    'transactions_count'  => 0,
                ],
            ]);
        }

        // Aggregate stats in a single query
        $stats = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('status', 'success')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type IN ('deposit', 'payment_release', 'refund') THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN type IN ('withdrawal', 'escrow_hold') THEN amount ELSE 0 END), 0) as total_spent,
                COUNT(*) as transactions_count
            ")
            ->first();

        // Pending escrow (held funds not yet released)
        $pendingEscrow = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'escrow_hold')
            ->where('status', 'pending')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Informasi wallet berhasil diambil.',
            'data'    => [
                'balance'             => $wallet->balance,
                'total_income'        => $stats->total_income ?? '0.00',
                'total_spent'         => $stats->total_spent ?? '0.00',
                'pending_escrow'      => $pendingEscrow,
                'transactions_count'  => (int) ($stats->transactions_count ?? 0),
            ],
        ]);
    }

    /**
     * Get the authenticated user's wallet transaction history.
     *
     * GET /api/v1/wallet/transactions
     *
     * Query params:
     *   - type: filter by transaction type (deposit, withdrawal, escrow_hold, payment_release, refund, admin_fee)
     *   - status: filter by status (pending, success, failed)
     *   - per_page: number of items per page (default 15, max 50)
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada transaksi.',
                'data'    => [
                    'transactions' => [],
                ],
            ]);
        }

        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) $request->input('per_page', 15), 50);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data'    => WalletTransactionResource::collection($query->paginate($perPage))->response()->getData(true),
        ]);
    }
}
