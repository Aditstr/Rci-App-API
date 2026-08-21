<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('role', 'paralegal')->first();
$profile = $user->expertProfile;

$updateData = [
    'verification_status' => 'pending',
    'rejection_reason'    => null,
    'verified_at'         => null,
];

try {
    \Illuminate\Support\Facades\DB::transaction(function () use ($profile, $updateData) {
        $profile->update($updateData);
    });
    echo "Success";
} catch (\Exception $e) {
    echo $e->getMessage();
}
