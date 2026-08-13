<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalCaseResource;
use App\Models\LegalCase;
use App\Notifications\CaseStatusUpdated;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseCompletionController extends Controller
{
    public function __construct(
        protected EscrowService $escrowService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // 1. Expert marks case as done → awaiting_confirmation
    // ──────────────────────────────────────────────────────────────

    /**
     * Expert marks the case as completed.
     * Status transitions to 'awaiting_confirmation' until client confirms.
     *
     * POST /api/v1/expert/cases/{id}/complete
     *
     * Body: { "completion_notes": "Kasus selesai ditangani..." }
     */
    public function expertComplete(Request $request, $id): JsonResponse
    {
        $request->validate([
            'completion_notes' => ['required', 'string', 'max:5000'],
        ], [
            'completion_notes.required' => 'Catatan penyelesaian wajib diisi sebagai rangkuman hasil kerja.',
            'completion_notes.max'      => 'Catatan penyelesaian maksimal 5000 karakter.',
        ]);

        $case = LegalCase::findOrFail($id);

        Gate::authorize('completeCase', $case);

        $case->update([
            'status'              => 'awaiting_confirmation',
            'completion_notes'    => $request->input('completion_notes'),
            'expert_completed_at' => now(),
        ]);

        // Notify the client
        $case->load('client');
        if ($case->client) {
            $case->client->notify(new CaseStatusUpdated(
                $case,
                "Expert telah menyelesaikan kasus #{$case->case_number} \"{$case->title}\". "
                . "Silakan konfirmasi penyelesaian atau ajukan keberatan jika ada masalah."
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Kasus ditandai selesai. Menunggu konfirmasi dari klien.',
            'data'    => new LegalCaseResource($case->load(['client', 'expert.expertProfile', 'documents'])),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 2. Client confirms completion → completed + escrow release
    // ──────────────────────────────────────────────────────────────

    /**
     * Client confirms the case is completed satisfactorily.
     * This triggers:
     *  - Case status → 'completed'
     *  - Escrow release (90% to expert, 10% platform fee)
     *  - Notifications to expert
     *
     * POST /api/v1/cases/{id}/confirm-completion
     */
    public function clientConfirm(Request $request, $id): JsonResponse
    {
        $case = LegalCase::findOrFail($id);

        Gate::authorize('confirmCompletion', $case);

        // Finalize the case
        $case->update([
            'status'              => 'completed',
            'completed_at'        => now(),
            'client_confirmed_at' => now(),
        ]);

        // Release escrow funds (90% expert, 10% platform)
        try {
            $this->escrowService->releaseFunds($case);
        } catch (\RuntimeException $e) {
            // Log the escrow failure but don't rollback the completion
            // Admin can manually release funds from the panel
            \Illuminate\Support\Facades\Log::error(
                "Escrow release gagal untuk kasus #{$case->case_number}: " . $e->getMessage()
            );

            // Notify admin about the escrow failure
            return response()->json([
                'success' => true,
                'message' => 'Kasus dikonfirmasi selesai. Catatan: Pencairan dana memerlukan tindakan admin.',
                'data'    => new LegalCaseResource($case->load(['client', 'expert.expertProfile'])),
                'warning' => 'Escrow release memerlukan tindakan manual dari admin.',
            ]);
        }

        // Notify the expert about completion + payment
        $case->load('expert');
        if ($case->expert) {
            $case->expert->notify(new CaseStatusUpdated(
                $case,
                "Klien telah mengkonfirmasi penyelesaian kasus #{$case->case_number}. "
                . "Dana telah dicairkan ke wallet Anda. Terima kasih atas kerja profesional Anda!"
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Kasus dikonfirmasi selesai. Dana telah dicairkan ke expert.',
            'data'    => new LegalCaseResource($case->load(['client', 'expert.expertProfile'])),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Client disputes the completion → dispute
    // ──────────────────────────────────────────────────────────────

    /**
     * Client disputes the case completion.
     * This freezes the escrow and requires admin intervention.
     *
     * POST /api/v1/cases/{id}/dispute
     *
     * Body: { "dispute_reason": "Hasil tidak sesuai dengan..." }
     */
    public function clientDispute(Request $request, $id): JsonResponse
    {
        $request->validate([
            'dispute_reason' => ['required', 'string', 'max:2000'],
        ], [
            'dispute_reason.required' => 'Alasan keberatan wajib diisi.',
            'dispute_reason.max'      => 'Alasan keberatan maksimal 2000 karakter.',
        ]);

        $case = LegalCase::findOrFail($id);

        Gate::authorize('disputeCase', $case);

        $case->update([
            'status'         => 'dispute',
            'dispute_reason' => $request->input('dispute_reason'),
        ]);

        // Notify the expert about the dispute
        $case->load(['expert', 'client']);
        if ($case->expert) {
            $case->expert->notify(new CaseStatusUpdated(
                $case,
                "Klien mengajukan keberatan terhadap penyelesaian kasus #{$case->case_number}. "
                . "Alasan: {$request->input('dispute_reason')}. "
                . "Tim admin akan meninjau kasus ini."
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Keberatan berhasil diajukan. Tim admin akan meninjau kasus ini.',
            'data'    => new LegalCaseResource($case),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Client cancels the case → cancelled + refund
    // ──────────────────────────────────────────────────────────────

    /**
     * Client cancels a case before it is actively being worked on.
     * This triggers an automatic escrow refund if funds were locked.
     *
     * POST /api/v1/cases/{id}/cancel
     *
     * Body: { "cancellation_reason": "Saya sudah tidak membutuhkan..." }
     */
    public function clientCancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $case = LegalCase::findOrFail($id);

        Gate::authorize('cancelCase', $case);

        // Update status to cancelled — this triggers the model observer
        // which automatically calls EscrowService::refundEscrow()
        $case->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->input('cancellation_reason', 'Dibatalkan oleh klien.'),
        ]);

        // Notify the expert if assigned
        $case->load('expert');
        if ($case->expert) {
            $case->expert->notify(new CaseStatusUpdated(
                $case,
                "Klien telah membatalkan kasus #{$case->case_number}. "
                . "Alasan: " . ($request->input('cancellation_reason') ?: 'Tidak ada alasan yang diberikan.')
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Kasus berhasil dibatalkan.' . ($case->proposed_fee ? ' Dana escrow telah dikembalikan.' : ''),
            'data'    => new LegalCaseResource($case),
        ]);
    }
}
