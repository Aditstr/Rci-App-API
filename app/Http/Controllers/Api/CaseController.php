<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Resources\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseController extends Controller
{
    public function __construct(
        protected EscrowService $escrowService,
    ) {}

    /**
     * Get the active or recent cases for the authenticated client.
     * Includes the assigned expert profile if available.
     *
     * GET /api/cases
     */
    public function index(Request $request): JsonResponse
    {
        $cases = LegalCase::with(['expert.expertProfile', 'documents'])
            ->where('client_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kasus berhasil diambil.',
            'data'    => LegalCaseResource::collection($cases)->response()->getData(true),
        ]);
    }

    /**
     * Get a specific case detail for the tracking/dashboard view.
     *
     * GET /api/cases/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $case = LegalCase::with(['expert.expertProfile', 'documents'])
            ->findOrFail($id);

        Gate::authorize('view', $case);

        return response()->json([
            'success' => true,
            'message' => 'Detail kasus berhasil diambil.',
            'data'    => new LegalCaseResource($case),
        ]);
    }

    /**
     * Submit a new case from the dashboard 'Ajukan Kasus Baru'.
     *
     * POST /api/cases
     */
    /**
     * Map Indonesian category names from frontend to English enum values in DB.
     */
    private function mapCategory(string $category): string
    {
        $map = [
            // Frontend values (case_type)
            'perdata'               => 'general',
            'pidana'                => 'criminal',
            'tata_usaha'            => 'corporate',
            'tata usaha'            => 'corporate',
            // English values (direct pass)
            'criminal'              => 'criminal',
            'general'               => 'general',
            'family'                => 'family',
            'corporate'             => 'corporate',
            'property'              => 'property',
            'labor'                 => 'labor',
            'immigration'           => 'immigration',
            'intellectual_property' => 'intellectual_property',
            'tax'                   => 'tax',
            // Indonesian labels
            'hukum pidana'          => 'criminal',
            'hukum perdata'         => 'general',
            'hukum keluarga'        => 'family',
            'keluarga'              => 'family',
            'hukum perusahaan'      => 'corporate',
            'perusahaan'            => 'corporate',
            'korporasi'             => 'corporate',
            'hukum properti'        => 'property',
            'properti'              => 'property',
            'hukum ketenagakerjaan' => 'labor',
            'ketenagakerjaan'       => 'labor',
            'hukum imigrasi'        => 'immigration',
            'imigrasi'              => 'immigration',
            'hukum kekayaan intelektual' => 'intellectual_property',
            'kekayaan intelektual'  => 'intellectual_property',
            'hukum pajak'           => 'tax',
            'pajak'                 => 'tax',
            'umum'                  => 'general',
        ];

        return $map[strtolower(trim($category))] ?? 'general';
    }

    public function store(StoreCaseRequest $request): JsonResponse
    {
        try {
            $mappedCategory = $this->mapCategory(
                $request->input('category', $request->input('case_type', 'general'))
            );

            $case = LegalCase::create([
                'case_number'    => LegalCase::generateCaseNumber(),
                'client_id'      => $request->user()->id,
                'title'          => $request->input('title'),
                'description'    => $request->input('description'),
                'category'       => $mappedCategory,
                'status'         => 'submitted',
                'submitted_at'   => now(),
            ]);
            
            // Bypass cast to boolean
            \App\Models\LegalCase::where('id', $case->id)->update([
                'is_marketplace' => \Illuminate\Support\Facades\DB::raw('true')
            ]);
            $case->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Kasus berhasil diajukan.',
                'data'    => new LegalCaseResource($case),
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Case store DB error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kasus. Silakan coba lagi.',
            ], 500);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Case store error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────
    // Quotation Approval / Rejection
    // ──────────────────────────────────────────────

    /**
     * Client approves the lawyer's proposed quotation.
     * This locks the proposed fee in escrow and activates the case.
     *
     * POST /api/cases/{id}/quotation/approve
     */
    public function approveQuotation(Request $request, $id): JsonResponse
    {
        $case = LegalCase::findOrFail($id);

        Gate::authorize('manageQuotation', $case);

        if (! $case->proposed_fee || $case->proposed_fee <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kasus ini belum memiliki quotation yang valid dari pengacara.',
            ], 422);
        }

        try {
            // Lock the proposed fee in escrow from the client's wallet
            $this->escrowService->lockFundsForCase($case, (float) $case->proposed_fee);

            // Update quotation status
            $case->update(['quotation_status' => 'approved']);

            // Notify the expert
            $case->load('expert');
            if ($case->expert) {
                $case->expert->notify(
                    new \App\Notifications\CaseStatusUpdated(
                        $case,
                        "Klien telah menyetujui quotation Anda sebesar Rp " . number_format((float) $case->proposed_fee, 0, ',', '.') . " untuk kasus #{$case->case_number}."
                    )
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation disetujui dan dana telah dikunci dalam escrow.',
                'data'    => new LegalCaseResource($case),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Client rejects the lawyer's proposed quotation.
     * The lawyer can then revise and re-submit a new quotation.
     *
     * POST /api/cases/{id}/quotation/reject
     * Body: { "reason": "Terlalu mahal" }
     */
    public function rejectQuotation(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $case = LegalCase::findOrFail($id);

        Gate::authorize('manageQuotation', $case);

        $case->update([
            'quotation_status' => 'rejected',
        ]);

        // Notify the expert about rejection
        $case->load('expert');
        if ($case->expert) {
            $reason = $request->input('reason', 'Tidak ada alasan yang diberikan.');
            $case->expert->notify(
                new \App\Notifications\CaseStatusUpdated(
                    $case,
                    "Klien menolak quotation Anda untuk kasus #{$case->case_number}. Alasan: {$reason}. Silakan ajukan quotation baru."
                )
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Quotation ditolak. Pengacara akan diberitahu untuk mengajukan quotation baru.',
            'data'    => new LegalCaseResource($case),
        ]);
    }
}

