<?php

use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('role', 'client')->first();
if ($user) {
    echo $user->email . "\n";
    echo $user->createToken('test-token')->plainTextToken . "\n";
} else {
    echo "No client found\n";
}
