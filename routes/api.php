<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\JobMarketplaceController;
use App\Http\Controllers\Api\LawyerDashboardController;
use App\Http\Controllers\Api\ParalegalDashboardController;
use App\Http\Controllers\Api\RciApiController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ChatController;
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
            return response()->json([
                'message' => 'Silakan buka link ini melalui aplikasi RCI App atau Frontend Web Anda untuk mengubah password.',
                'token' => $token
            ]);
        })->name('password.reset');

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
        ->name('verification.send');

    // ──────────────────────────────────────────────
    // RCI API — Authenticated (Sanctum) & Verified Email
    // ──────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->prefix('rci')->group(function () {
        // AI Chat — all authenticated users
        Route::post('/chat', [RciApiController::class, 'chat'])->name('api.v1.rci.chat');

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

});


