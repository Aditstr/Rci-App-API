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
        // Update using query builder to bypass Eloquent boolean casting for DB::raw
        \App\Models\LegalCase::where('id', $case->id)->update([
            'expert_id' => $user->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'is_marketplace' => \Illuminate\Support\Facades\DB::raw('false')
        ]);
        // Refresh the model to get the updated values
        $case->refresh();

        // Tambahkan pendapatan Rp20.000 ke dompet Paralegal
        $user->wallet->balance += 20000;
        $user->wallet->save();

        \App\Models\WalletTransaction::create([
            'wallet_id' => $user->wallet->id,
            'amount' => 20000,
            'type' => 'deposit',
            'reference_id' => $case->id,
            'reference_type' => get_class($case),
            'status' => 'completed',
            'description' => 'Pendapatan ambil kasus baru #' . $case->id
        ]);

        // Eager load client to avoid N+1 lazy loading
        $case->load('client');

        // Notify the client that their case has been taken
        $case->client->notify(new \App\Notifications\CaseStatusUpdated($case, "Kasus Anda telah diambil oleh pakar hukum " . $user->name . " dan sedang diproses."));

        return response()->json([
            'success' => true,
            'message' => 'Berhasil melamar kasus dan menugaskan ke profil Anda.',
            'data'    => new LegalCaseResource($case)
        ]);
    }
}
