<?php

use Illuminate\Support\Facades\Route;
use App\Models\ExpertProfile;
use Illuminate\Support\Facades\Storage;

// ─── Public Documentation ──────────────────────────────
Route::get('/api-docs', fn() => view('api-docs'));
Route::get('/swagger.yaml', function () {
    return response()->file(public_path('swagger.yaml'));
});

// ─── Google OAuth Hash Callback ───────────────────────
Route::get('/auth/google/callback', fn() => view('auth.google-callback'));

// ─── Vue 3 SPA Primary Routes ─────────────────────────
Route::get('/', fn() => view('app'));
Route::get('/login', fn() => view('app'));
Route::get('/register', fn() => view('app'));
Route::get('/client', fn() => view('app'));
Route::get('/client/ai-chat', fn() => view('app'));

// ─── Preserved Production Sub-Pages (Blade) ────────────
Route::prefix('client')->group(function () {
    Route::get('/cases',         fn() => view('client.cases'));
    Route::get('/cases/new',     fn() => view('client.case-new'));
    Route::get('/cases/{id}',    fn() => view('client.case-detail'));
    Route::get('/wallet',        fn() => view('client.wallet'));
    Route::get('/notifications', fn() => view('client.notifications'));
    Route::get('/profile',       fn() => view('client.profile'));
});

// ─── Payment Result Pages (Xendit Redirect) ───────────
Route::get('/payment/success', fn() => view('payment.success'));
Route::get('/payment/failed',  fn() => view('payment.failed'));

// ─── Paralegal Workspace (Blade) ──────────────────────
Route::prefix('paralegal')->group(function () {
    Route::get('/',             fn() => view('paralegal.dashboard'));
    Route::get('/kanban',       fn() => view('paralegal.dashboard'));
    Route::get('/marketplace',  fn() => view('paralegal.marketplace'));
    Route::get('/wallet',       fn() => view('paralegal.wallet'));
    Route::get('/cases/{id}',   fn() => view('paralegal.case-detail'));
});

// ─── Lawyer Dashboard (Blade) ─────────────────────────
Route::prefix('lawyer')->group(function () {
    Route::get('/',           fn() => view('lawyer.dashboard'));
    Route::get('/cases',      fn() => view('lawyer.cases'));
    Route::get('/cases/{id}', fn() => view('paralegal.case-detail'));
    Route::get('/revenue',    fn() => view('lawyer.revenue'));
    Route::get('/wallet',     fn() => view('lawyer.wallet'));
});

// ── Admin: Download Expert Documents ────────────────────────
Route::get('/admin/expert/{profile}/document/{type}', function (ExpertProfile $profile, string $type) {
    // Security: Only admins can download expert documents (KTP, ijazah, etc.)
    if (! auth()->user() || ! auth()->user()->isAdmin()) {
        abort(403, 'Akses ditolak. Hanya admin yang dapat mengunduh dokumen ini.');
    }

    $allowedTypes = ['ktp', 'ijazah', 'license', 'selfie', 'cv'];
    if (! in_array($type, $allowedTypes, true)) {
        abort(404, 'Tipe dokumen tidak valid.');
    }

    $pathMap = [
        'ktp'     => $profile->ktp_path,
        'ijazah'  => $profile->ijazah_path,
        'license' => $profile->license_card_path,
        'selfie'  => $profile->selfie_path,
        'cv'      => $profile->cv_path,
    ];

    $path = $pathMap[$type] ?? null;

    if (! $path || ! Storage::disk()->exists($path)) {
        abort(404, 'Dokumen tidak ditemukan.');
    }

    return Storage::disk()->download($path);
})->middleware(['web', 'auth'])->name('expert.document.download');

// ─── SPA Catch-All Fallback ───────────────────────────
Route::get('/{any}', fn() => view('app'))->where('any', '^(?!api|admin|swagger|api-docs).*$');
