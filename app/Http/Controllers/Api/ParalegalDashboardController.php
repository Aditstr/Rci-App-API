<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\EscrowService;
use App\Http\Requests\StoreCaseByAgentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ParalegalDashboardController extends Controller
{
    public function __construct(
        protected EscrowService $escrowService,
    ) {}
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

    /**
     * Daftarkan kasus baru atas nama warga desa (Client on behalf / COD).
     * 
     * POST /api/paralegal/cases
     */
    public function storeCase(StoreCaseByAgentRequest $request): JsonResponse
    {
        $paralegal = $request->user();

        // 1. Map Category
        $mappedCategory = $this->mapCategory($request->input('category'));

        try {
            return DB::transaction(function () use ($request, $paralegal, $mappedCategory) {
                
                // 2. Find or create Client (Shadow Account) using client_phone
                $phone = trim($request->input('client_phone'));
                $client = User::where('phone', $phone)->first();

                if (!$client) {
                    // Generate a random email that doesn't conflict
                    $email = 'shadow_' . $phone . '_' . Str::random(5) . '@rci-app.id';
                    
                    $client = User::create([
                        'name'        => $request->input('client_name'),
                        'phone'       => $phone,
                        'email'       => $email,
                        'role'        => 'client',
                        'password'    => bcrypt(Str::random(16)),
                        'is_verified' => true,
                        'is_active'   => true,
                    ]);
                } else {
                    // Consent Gap Protection: Block real accounts from being registered silently
                    if (!\Illuminate\Support\Str::startsWith($client->email, 'shadow_')) {
                        throw new \RuntimeException('Nomor HP ini sudah terdaftar sebagai akun riil. Warga harus mendaftar dari aplikasinya sendiri.');
                    }
                }

                // 3. Create Case
                $case = LegalCase::create([
                    'case_number'    => LegalCase::generateCaseNumber(),
                    'client_id'      => $client->id,
                    'expert_id'      => $paralegal->id,
                    'title'          => $request->input('title'),
                    'description'    => $request->input('description'),
                    'category'       => $mappedCategory,
                    'status'         => 'submitted', // awal submitted, akan menjadi active saat funds di-lock
                    'submitted_at'   => now(),
                    'assigned_at'    => now(),
                    'is_marketplace' => false, // langsung dipegang oleh agen, tidak masuk marketplace
                ]);

                // 4. Lock funds in escrow from the PARALEGAL's wallet
                $amount = (float) $request->input('amount');
                $this->escrowService->lockFundsForCase($case, $amount, $paralegal);

                return response()->json([
                    'success' => true,
                    'message' => 'Kasus warga desa berhasil didaftarkan dan diaktifkan via COD.',
                    'data'    => [
                        'case'   => $case->load(['client', 'documents']),
                        'client' => $client,
                    ]
                ], 201);
            });
        } catch (\RuntimeException $e) {
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

    /**
     * Map Indonesian category names from frontend to English enum values in DB.
     */
    private function mapCategory(string $category): string
    {
        $map = [
            'hukum pidana'          => 'criminal',
            'pidana'                => 'criminal',
            'criminal'              => 'criminal',
            'hukum perdata'         => 'general',
            'perdata'               => 'general',
            'hukum keluarga'        => 'family',
            'keluarga'              => 'family',
            'family'                => 'family',
            'hukum perusahaan'      => 'corporate',
            'perusahaan'            => 'corporate',
            'korporasi'             => 'corporate',
            'corporate'             => 'corporate',
            'hukum properti'        => 'property',
            'properti'              => 'property',
            'property'              => 'property',
            'hukum ketenagakerjaan' => 'labor',
            'ketenagakerjaan'       => 'labor',
            'labor'                 => 'labor',
            'hukum imigrasi'        => 'immigration',
            'imigrasi'              => 'immigration',
            'immigration'           => 'immigration',
            'hukum kekayaan intelektual' => 'intellectual_property',
            'kekayaan intelektual'  => 'intellectual_property',
            'intellectual_property' => 'intellectual_property',
            'hukum pajak'           => 'tax',
            'pajak'                 => 'tax',
            'tax'                   => 'tax',
            'umum'                  => 'general',
            'general'               => 'general',
        ];

        return $map[strtolower(trim($category))] ?? 'general';
    }
}

