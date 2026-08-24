<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalCaseResource;
use App\Models\LegalCase;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LawyerDashboardController extends Controller
{
    /**
     * Dapatkan statistik ringkas untuk header Dashboard Pengacara (The Specialist).
     * 
     * GET /api/lawyer/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user()->load(['expertProfile', 'wallet']);

        // Rujukan Baru (Kasus dari paralegal yang di-escalated dan ditugaskan ke role lawyer ini)
        $newReferralsCount = LegalCase::where('expert_id', $user->id)
            ->where('status', 'escalated')
            ->count();

        // Kasus dalam pengerjaan (In Progress / Reviewing)
        $activeCasesCount = LegalCase::where('expert_id', $user->id)
            ->whereIn('status', ['in_progress', 'reviewing'])
            ->count();

        // Kasus "Selesai" pengacara
        $completedCount = LegalCase::where('expert_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Rating pengacara
        $rating = $user->expertProfile ? $user->expertProfile->rating : 0.0;

        // Pendapatan profesional yang benar-benar sudah masuk ke wallet.
        $revenueQuery = WalletTransaction::query()
            ->where('wallet_id', $user->wallet?->id ?? 0)
            ->where('type', 'payment_release')
            ->where('status', 'success');

        $totalRevenue = (clone $revenueQuery)->sum('amount');
        $monthlyRevenue = (clone $revenueQuery)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Statistik Lawyer Dashboard',
            'data' => [
                'new_referrals_count' => $newReferralsCount,
                'active_cases_count'  => $activeCasesCount,
                'completed_count'     => $completedCount,
                'total_revenue'       => $totalRevenue,
                'monthly_revenue'     => $monthlyRevenue,
                'rating'              => $rating,
            ]
        ]);
    }

    /**
     * Mengajukan Quotation Harga Kasus (Proposed Legal Fee & Schema)
     * kepada Klien.
     * 
     * POST /api/lawyer/cases/{case_id}/quote
     */
    public function sendQuotation(Request $request, $case_id): JsonResponse
    {
        $request->validate([
            'proposed_fee' => 'required|numeric|min:1000',
            'fee_notes'    => 'nullable|string|max:1000'
        ]);

        $case = LegalCase::findOrFail($case_id);

        Gate::authorize('sendQuotation', $case);

        $case->proposed_fee = $request->input('proposed_fee');
        $case->fee_notes = $request->input('fee_notes');
        
        // Fee Structure JSON Schema (contoh: Litigation)
        $case->fee_structure = [
            'type'            => 'litigation',
            'lawyer_share'    => 60,   // %
            'paralegal_share' => 30,   // %
            'platform_fee'    => 10,   // %
        ];

        $case->quotation_status = 'pending_client_approval';
        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Quotation beserta skema bagi hasil (Fee Split) berhasil diajukan ke Klien.',
            'data'    => new LegalCaseResource($case)
        ]);
    }

    /**
     * Data Professional Revenue (Pendapatan Kotor & Riwayat Gaji Profesional).
     * 
     * GET /api/lawyer/revenue
     */
    public function revenueInfo(Request $request): JsonResponse
    {
        $user = $request->user();

        $completedCases = LegalCase::where('expert_id', $user->id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Informasi Revenue Profesional',
            'data' => [
                'completed_cases' => $completedCases,
            ]
        ]);
    }
}
