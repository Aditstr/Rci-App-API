<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\JobMarketplaceController;
use App\Http\Controllers\Api\LawyerDashboardController;
use App\Http\Controllers\Api\ParalegalDashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RciApiController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\XenditWebhookController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CaseCompletionController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes registered here are automatically prefixed with /api
| and assigned the "api" middleware group by Laravel.
|
*/

// ──────────────────────────────────────────────
// API Version 1
// ──────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // ──────────────────────────────────────────────
    // AI Chat (Freemium — accessible with or without auth)
    // ──────────────────────────────────────────────
    Route::post('/chat/send', [AiChatController::class, 'send'])
        ->middleware('throttle:ai_chat')
        ->name('api.v1.chat.send');

    // ──────────────────────────────────────────────
    // Authentication (Sanctum)
    // ──────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        // Public — with stricter throttle
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:auth')
            ->name('api.v1.auth.register');
        Route::post('/login',    [AuthController::class, 'login'])
            ->middleware('throttle:auth')
            ->name('api.v1.auth.login');
            
        // Password Reset
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth')
            ->name('api.v1.auth.forgot_password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth')
            ->name('api.v1.auth.reset_password');

        // Required by Laravel's built-in ResetPassword Notification to generate the email link
        Route::get('/reset-password/{token}', function (string $token) {
            // Security: Do not expose token in JSON response — redirect to frontend
            $frontendUrl = config('app.frontend_url', config('app.url'));
            return redirect("{$frontendUrl}/reset-password?token={$token}");
        })->name('password.reset');

        // Google OAuth
        Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('api.v1.auth.google');
        Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('api.v1.auth.google.callback');

        // Protected
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('/me',      [AuthController::class, 'me'])->name('api.v1.auth.me');

            // Re-submit documents after rejection (lawyer/paralegal)
            Route::post('/resubmit-documents', [AuthController::class, 'resubmitDocuments'])
                ->name('api.v1.auth.resubmit_documents');
        });
    });

    // ──────────────────────────────────────────────
    // Email Verification
    // ──────────────────────────────────────────────
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');

    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->middleware('throttle:auth')
        ->name('verification.send');

    // ──────────────────────────────────────────────
    // Profile Management (All authenticated & verified users)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->prefix('profile')->group(function () {
        Route::get('/',         [ProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::post('/',        [ProfileController::class, 'update'])->name('api.v1.profile.update');
        Route::put('/password', [ProfileController::class, 'changePassword'])->name('api.v1.profile.password');
        Route::delete('/avatar',[ProfileController::class, 'deleteAvatar'])->name('api.v1.profile.avatar.delete');

        // Expert Profile (paralegal & lawyer only)
        Route::put('/expert',   [ProfileController::class, 'updateExpertProfile'])->name('api.v1.profile.expert.update');
    });

    // ──────────────────────────────────────────────
    // RCI AI Chat — Authenticated only (no email verification required)
    // ──────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/rci/chat', [RciApiController::class, 'chat'])->name('api.v1.rci.chat');
    });

    // ──────────────────────────────────────────────
    // RCI API — Authenticated (Sanctum) & Verified Email
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->prefix('rci')->group(function () {
        // Wallet Info — all authenticated users (client, paralegal, lawyer)
        Route::get('/wallet',              [WalletController::class, 'show'])->name('api.v1.rci.wallet.show');
        Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('api.v1.rci.wallet.transactions');

        // Wallet Actions & Escrow — client only
        Route::middleware('role:client')->group(function () {
            Route::post('/topup',        [RciApiController::class, 'topup'])->name('api.v1.rci.topup');
            Route::post('/upgrade',      [RciApiController::class, 'upgrade'])->name('api.v1.rci.upgrade');
            Route::post('/escrow/start', [RciApiController::class, 'startCase'])->name('api.v1.rci.escrow.start');
        });
    });

    // ──────────────────────────────────────────────
    // Notifications (All authenticated & verified users)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->prefix('notifications')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount'])->name('api.v1.notifications.unread_count');
        Route::post('/{id}/read',   [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->name('api.v1.notifications.mark_as_read');
        Route::post('/read-all',    [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.mark_all_as_read');
    });

    // ──────────────────────────────────────────────
    // Case Management (Client Only)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified', 'role:client'])->prefix('cases')->group(function () {
        Route::get('/', [CaseController::class, 'index'])->name('api.v1.cases.index');
        Route::get('/{id}', [CaseController::class, 'show'])->name('api.v1.cases.show');
        Route::post('/', [CaseController::class, 'store'])->name('api.v1.cases.store');

        // Document Upload
        Route::post('/{case_id}/documents', [\App\Http\Controllers\Api\CaseDocumentController::class, 'store'])->name('api.v1.cases.documents.store');

        // Quotation Approval / Rejection
        Route::post('/{id}/quotation/approve', [CaseController::class, 'approveQuotation'])->name('api.v1.cases.quotation.approve');
        Route::post('/{id}/quotation/reject',  [CaseController::class, 'rejectQuotation'])->name('api.v1.cases.quotation.reject');

        // Case Chat Room — client side
        Route::get('/{id}/messages', [ChatController::class, 'index'])->name('api.v1.cases.messages.index');
        Route::post('/{id}/messages', [ChatController::class, 'store'])->name('api.v1.cases.messages.store');
        Route::put('/{id}/messages/read', [ChatController::class, 'markAsRead'])->name('api.v1.cases.messages.read');

        // Case Completion Flow (Client actions)
        Route::post('/{id}/confirm-completion', [CaseCompletionController::class, 'clientConfirm'])->name('api.v1.cases.confirm_completion');
        Route::post('/{id}/dispute',            [CaseCompletionController::class, 'clientDispute'])->name('api.v1.cases.dispute');
        Route::post('/{id}/cancel',             [CaseCompletionController::class, 'clientCancel'])->name('api.v1.cases.cancel');

        // Review & Rating
        Route::post('/{id}/review', [ReviewController::class, 'store'])->name('api.v1.cases.review.store');
    });

    // ──────────────────────────────────────────────
    // Expert Chat & Document Access (Paralegal & Lawyer)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified', 'role:paralegal,lawyer', 'expert.verified'])->prefix('expert/cases')->group(function () {
        Route::get('/{id}/messages', [ChatController::class, 'index'])->name('api.v1.expert.cases.messages.index');
        Route::post('/{id}/messages', [ChatController::class, 'store'])->name('api.v1.expert.cases.messages.store');
        Route::put('/{id}/messages/read', [ChatController::class, 'markAsRead'])->name('api.v1.expert.cases.messages.read');
        
        // Document Upload
        Route::post('/{case_id}/documents', [\App\Http\Controllers\Api\CaseDocumentController::class, 'store'])->name('api.v1.expert.cases.documents.store');

        // Case Completion Flow (Expert action)
        Route::post('/{id}/complete', [CaseCompletionController::class, 'expertComplete'])->name('api.v1.expert.cases.complete');
    });

    // ──────────────────────────────────────────────
    // Paralegal Workspace (Dashboard & Kanban)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified', 'role:paralegal', 'expert.verified'])->prefix('paralegal')->group(function () {
        // Stats & Kanban Board
        Route::get('/dashboard/stats', [ParalegalDashboardController::class, 'stats'])->name('api.v1.paralegal.stats');
        Route::get('/cases',           [ParalegalDashboardController::class, 'cases'])->name('api.v1.paralegal.cases');
        Route::post('/cases',          [ParalegalDashboardController::class, 'storeCase'])->name('api.v1.paralegal.cases.store');
        Route::post('/cases/{id}/status', [ParalegalDashboardController::class, 'updateStatus'])->name('api.v1.paralegal.cases.updateStatus');
        Route::post('/cases/{id}/escalate', [ParalegalDashboardController::class, 'escalate'])->name('api.v1.paralegal.cases.escalate');

        // Job Marketplace (Apply Cases)
        Route::get('/marketplace',                [JobMarketplaceController::class, 'index'])->name('api.v1.paralegal.marketplace.index');
        Route::post('/marketplace/{case_id}/apply', [JobMarketplaceController::class, 'apply'])->name('api.v1.paralegal.marketplace.apply');
    });

    // ──────────────────────────────────────────────
    // Lawyer Dashboard (The Specialist)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified', 'role:lawyer', 'expert.verified'])->prefix('lawyer')->group(function () {
        Route::get('/dashboard/stats',   [LawyerDashboardController::class, 'stats'])->name('api.v1.lawyer.stats');
        Route::post('/cases/{case_id}/quote', [LawyerDashboardController::class, 'sendQuotation'])->name('api.v1.lawyer.cases.quote');
        Route::get('/revenue',           [LawyerDashboardController::class, 'revenueInfo'])->name('api.v1.lawyer.revenue');
    });

    // ──────────────────────────────────────────────
    // Xendit Webhooks (Public — no auth required)
    // ──────────────────────────────────────────────
    // Xendit sends callbacks to this endpoint when invoice status changes.
    // Authentication is handled via x-callback-token header verification.
    Route::post('/xendit/webhook/invoice', [XenditWebhookController::class, 'handleInvoiceCallback'])
        ->name('api.v1.xendit.webhook.invoice');

    // ──────────────────────────────────────────────
    // Payments (Authenticated)
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->prefix('payments')->group(function () {
        Route::get('/',            [PaymentController::class, 'index'])->name('api.v1.payments.index');
        Route::get('/{id}/status', [PaymentController::class, 'status'])->name('api.v1.payments.status');
    });

});


