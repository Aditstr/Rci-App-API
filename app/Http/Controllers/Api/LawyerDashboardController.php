<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawyerDashboardController extends Controller
{
    /**
     * Dapatkan statistik ringkas untuk header Dashboard Pengacara (The Specialist).
     * 
     * GET /api/lawyer/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

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

        return response()->json([
            'success' => true,
            'message' => 'Statistik Lawyer Dashboard',
            'data' => [
                'new_referrals_count' => $newReferralsCount,
                'active_cases_count'  => $activeCasesCount,
                'completed_count'     => $completedCount,
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
            'proposed_fee' => 'required|numeric|min:0',
            'fee_notes'    => 'nullable|string|max:1000'
        ]);

        $case = LegalCase::where('expert_id', $request->user()->id)->findOrFail($case_id);

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
            'data'    => $case
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
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Informasi Pajak dan Penarikan Dana Profesional',
            'data' => [
                'available_balance' => $wallet->balance,
                'ytd_gross_income'  => $wallet->balance, // Dalam integrasi penuh, YTD akan mendotalkan table payment_logs per tahun.
                'completed_cases'   => LegalCase::where('expert_id', $user->id)->where('status', 'completed')->count(), 
            ]
        ]);
    }
}
