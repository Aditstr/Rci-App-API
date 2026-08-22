<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $data = \App\Models\LegalCase::whereNotNull('expert_id')
                ->orderByDesc('created_at')
                ->get()
                ->toArray();
    print_r($data);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
