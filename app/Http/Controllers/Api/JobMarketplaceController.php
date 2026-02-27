<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobMarketplaceController extends Controller
{
    /**
     * Dapatkan daftar kasus yang terbuka untuk bidding/apply Paralegal.
     * 
     * GET /api/paralegal/marketplace
     */
    public function index(Request $request): JsonResponse
    {
        $query = LegalCase::whereNull('expert_id')
                          ->where('is_marketplace', true)
                          ->orderByDesc('created_at');

        // Optional filter: ?category=Hukum Perdata
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Feed Job Marketplace Paralegal',
            'data'    => $query->get()
        ]);
    }

    /**
     * Terapkan profil Paralegal ke sebuah kasus Terbuka
     * 
     * POST /api/paralegal/marketplace/{case_id}/apply
     */
    public function apply(Request $request, $case_id): JsonResponse
    {
        $case = LegalCase::where('is_marketplace', true)
            ->whereNull('expert_id')
            ->findOrFail($case_id);

        $user = $request->user();

        // Pemeriksaan kelayakan (Opsional): apakah paralegal terverifikasi SOP?
        if (!$user->expertProfile || !$user->expertProfile->is_verified) {
             return response()->json([
                 'success' => false,
                 'message' => 'Anda harus lulus Ujian SOP (Terverifikasi) untuk mengambil kasus.'
             ], 403);
        }

        /**
         * Di rilis aktual kita mungkin perlu tabel pivot (bidding/lamaran) agar ahli hukum di-review 
         * klien terlebih dahulu, namun untuk prototipe, kita dapat meng-assign langsung Paralegal ini 
         * ke dalam kasus jika mereka Apply.
         */
        $case->expert_id = $user->id;
        $case->status = 'assigned';
        $case->assigned_at = now();
        $case->is_marketplace = false; // Karena sudah diambil
        $case->save();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melamar kasus dan menugaskan ke profil Anda.',
            'data'    => $case
        ]);
    }
}
