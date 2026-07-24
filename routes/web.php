<?php

use Illuminate\Support\Facades\Route;
use App\Models\ExpertProfile;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-docs', function () {
    return view('api-docs');
});

Route::get('/swagger.yaml', function () {
    return response()->file(public_path('swagger.yaml'));
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

    if (! $path || ! Storage::disk('local')->exists($path)) {
        abort(404, 'Dokumen tidak ditemukan.');
    }

    return Storage::disk('local')->download($path);
})->middleware(['web', 'auth'])->name('expert.document.download');
