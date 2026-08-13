<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LegalCase;
use App\Models\User;

class LegalCasePolicy
{
    /**
     * Determine whether the user can view the case details.
     */
    public function view(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id || $user->id === $case->expert_id;
    }

    /**
     * Determine whether the user can update the case status (e.g. in Kanban).
     */
    public function updateStatus(User $user, LegalCase $case): bool
    {
        return $user->id === $case->expert_id;
    }

    /**
     * Determine whether the client can approve or reject the quotation.
     */
    public function manageQuotation(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id && $case->quotation_status === 'pending_client_approval';
    }

    /**
     * Determine whether the lawyer can send a quotation.
     */
    public function sendQuotation(User $user, LegalCase $case): bool
    {
        return $user->id === $case->expert_id && $user->role === 'lawyer';
    }

    /**
     * Determine whether the user can access the case chat room.
     */
    public function message(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id || $user->id === $case->expert_id;
    }

    /**
     * Determine whether the user can upload documents to the case.
     */
    public function uploadDocument(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id || $user->id === $case->expert_id;
    }

    /**
     * Determine whether the paralegal can escalate the case to a lawyer.
     */
    public function escalate(User $user, LegalCase $case): bool
    {
        return $user->id === $case->expert_id && $user->role === 'paralegal' && !in_array($case->status, ['completed', 'cancelled']);
    }

    // ──────────────────────────────────────────────
    // Completion Flow
    // ──────────────────────────────────────────────

    /**
     * Determine whether the expert can mark the case as completed.
     */
    public function completeCase(User $user, LegalCase $case): bool
    {
        return $user->id === $case->expert_id && $case->canBeCompleted();
    }

    /**
     * Determine whether the client can confirm case completion.
     */
    public function confirmCompletion(User $user, LegalCase $case): bool
    {
        \Illuminate\Support\Facades\Log::info('confirmCompletion policy check:', [
            'user_id' => $user->id,
            'case_client_id' => $case->client_id,
            'status' => $case->status,
            'isAwaiting' => $case->isAwaitingConfirmation()
        ]);
        return $user->id === $case->client_id && $case->isAwaitingConfirmation();
    }

    /**
     * Determine whether the client can dispute the case completion.
     */
    public function disputeCase(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id && $case->canBeDisputed();
    }

    /**
     * Determine whether the client can cancel the case.
     */
    public function cancelCase(User $user, LegalCase $case): bool
    {
        return $user->id === $case->client_id && $case->canBeCancelled();
    }
}
