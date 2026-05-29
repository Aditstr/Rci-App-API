<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParalegalDashboardController extends Controller
{
    /**
     * Dapatkan statistik ringkas untuk header Dashboard Paralegal.
     * 
     * GET /api/paralegal/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Total Kasus "Baru" (Need Action = pending/assigned but not in progress)
        $newCasesCount = LegalCase::where('expert_id', $user->id)
            ->whereIn('status', ['pending', 'assigned'])
            ->count();

        // Kasus "Reviewing" (Sedang dikerjakan dalam Kanban)
        $reviewingCount = LegalCase::where('expert_id', $user->id)
            ->where('status', 'in_progress')
            ->count();

        // Kasus "Selesai"
        $completedCount = LegalCase::where('expert_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        // Pengecekan test / verifikasi SOP
        $isSopPassed = $user->expertProfile ? $user->expertProfile->is_verified : false;

        return response()->json([
            'success' => true,
            'message' => 'Statistik Paralegal Dashboard berhasil dimuat.',
            'data' => [
                'new_cases_count' => $newCasesCount,
                'reviewing_count' => $reviewingCount,
                'completed_count' => $completedCount,
                'is_sop_passed'   => $isSopPassed,
            ]
        ]);
    }

    /**
     * Dapatkan daftar kasus untuk Kanban Board (Bisa difilter status).
     * 
     * GET /api/paralegal/cases
     */
    public function cases(Request $request): JsonResponse
    {
        $query = LegalCase::with(['client', 'documents'])
            ->where('expert_id', $request->user()->id)
            ->orderByDesc('assigned_at');

        // Filter berdasarkan kolom status (Contoh: "need_action", "reviewing", dll)
        if ($request->has('status')) {
            $statusMap = [
                'need_action' => ['pending', 'assigned'],
                'reviewing'   => ['in_progress', 'reviewing'],
            ];
            
            $reqStatus = $request->input('status');
            if (array_key_exists($reqStatus, $statusMap)) {
                 $query->whereIn('status', $statusMap[$reqStatus]);
            } else {
                 $query->where('status', $reqStatus);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(15)
        ]);
    }

    /**
     * Memperbarui status / memindahkan kartu di Board Kanban
     * 
     * POST /api/paralegal/cases/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $case = LegalCase::where('expert_id', $request->user()->id)->findOrFail($id);
        
        $case->status = $request->input('status');
        $case->save();

        // Notify the client about the status update
        $case->client->notify(new \App\Notifications\CaseStatusUpdated($case, "Status kasus Anda telah diperbarui menjadi " . strtoupper($case->status) . " oleh " . $request->user()->name));

        return response()->json([
            'success' => true,
            'message' => 'Status kasus berhasil diperbarui.',
            'data'    => $case
        ]);
    }
}

