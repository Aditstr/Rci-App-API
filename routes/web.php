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
