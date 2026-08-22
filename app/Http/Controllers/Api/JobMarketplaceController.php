<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalCaseResource;
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
        $query = LegalCase::with(['client:id,name'])
                          ->whereNull('expert_id')
                          ->whereRaw('is_marketplace = true')
                          ->orderByDesc('created_at');

        // Optional filter: ?category=Hukum Perdata
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Feed Job Marketplace Paralegal',
            'data'    => LegalCaseResource::collection($query->paginate(15))->response()->getData(true)
        ]);
    }

    /**
     * Terapkan profil Paralegal ke sebuah kasus Terbuka
     * 
     * POST /api/paralegal/marketplace/{case_id}/apply
     */
    public function apply(Request $request, $case_id): JsonResponse
    {
        $case = LegalCase::whereRaw('is_marketplace = true')
            ->whereNull('expert_id')
            ->findOrFail($case_id);

        $user = $request->user()->load('expertProfile');

        // Pemeriksaan kelayakan (Opsional): apakah paralegal terverifikasi SOP?
        if (!$user->expertProfile || !$user->expertProfile->isApproved()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Anda harus lulus Ujian SOP (Terverifikasi) untuk mengambil kasus.'
             ], 403);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {

        /**
         * Di rilis aktual kita mungkin perlu tabel pivot (bidding/lamaran) agar ahli hukum di-review 
         * klien terlebih dahulu, namun untuk prototipe, kita dapat meng-assign langsung Paralegal ini 
         * ke dalam kasus jika mereka Apply.
         */
        // Update using query builder to bypass Eloquent boolean casting for DB::raw
        \App\Models\LegalCase::where('id', $case->id)->update([
            'expert_id' => $user->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'is_marketplace' => \Illuminate\Support\Facades\DB::raw('false')
        ]);
        // Refresh the model to get the updated values
        $case->refresh();

        // Notify the client that their case has been taken
        $case->client->notify(new \App\Notifications\CaseStatusUpdated($case, "Kasus Anda telah diambil oleh pakar hukum " . $user->name . " dan sedang diproses."));

        \Illuminate\Support\Facades\DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melamar kasus dan menugaskan ke profil Anda.',
            'data'    => new LegalCaseResource($case)
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        \Illuminate\Support\Facades\Log::error('Marketplace Apply Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil kasus. Silakan coba lagi. (' . $e->getMessage() . ')',
        ], 500);
    }
    }
}
