<?php

use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\JobMarketplaceController;
use App\Http\Controllers\Api\LawyerDashboardController;
use App\Http\Controllers\Api\ParalegalDashboardController;
use App\Http\Controllers\Api\RciApiController;
use App\Http\Controllers\Api\VerificationController;
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
// AI Chat (Freemium — accessible with or without auth)
// ──────────────────────────────────────────────
Route::post('/chat/send', [AiChatController::class, 'send'])
    ->name('api.chat.send');

// ──────────────────────────────────────────────
// Authentication (Sanctum)
// ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Public
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me',      [AuthController::class, 'me'])->name('auth.me');
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
    Route::post('/chat',         [RciApiController::class, 'chat'])->name('rci.chat');
    Route::post('/topup',        [RciApiController::class, 'topup'])->name('rci.topup');
    Route::post('/upgrade',      [RciApiController::class, 'upgrade'])->name('rci.upgrade');
    Route::post('/escrow/start', [RciApiController::class, 'startCase'])->name('rci.escrow.start');
});

// ──────────────────────────────────────────────
// Case Management (Client Dashboard)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'verified'])->prefix('cases')->group(function () {
    Route::get('/', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/{id}', [CaseController::class, 'show'])->name('cases.show');
    Route::post('/', [CaseController::class, 'store'])->name('cases.store');
});

// ──────────────────────────────────────────────
// Paralegal Workspace (Dashboard & Kanban)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'verified'])->prefix('paralegal')->group(function () {
    // Stats & Kanban Board
    Route::get('/dashboard/stats', [ParalegalDashboardController::class, 'stats'])->name('paralegal.stats');
    Route::get('/cases',           [ParalegalDashboardController::class, 'cases'])->name('paralegal.cases');
    Route::post('/cases/{id}/status', [ParalegalDashboardController::class, 'updateStatus'])->name('paralegal.cases.updateStatus');
    
    // Job Marketplace (Apply Cases)
    Route::get('/marketplace',                [JobMarketplaceController::class, 'index'])->name('paralegal.marketplace.index');
    Route::post('/marketplace/{case_id}/apply', [JobMarketplaceController::class, 'apply'])->name('paralegal.marketplace.apply');
});

// ──────────────────────────────────────────────
// Lawyer Dashboard (The Specialist)
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'verified'])->prefix('lawyer')->group(function () {
    Route::get('/dashboard/stats',   [LawyerDashboardController::class, 'stats'])->name('lawyer.stats');
    Route::post('/cases/{case_id}/quote', [LawyerDashboardController::class, 'sendQuotation'])->name('lawyer.cases.quote');
    Route::get('/revenue',           [LawyerDashboardController::class, 'revenueInfo'])->name('lawyer.revenue');
});

