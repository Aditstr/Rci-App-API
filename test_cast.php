<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$case = new \App\Models\LegalCase();
$case->is_marketplace = \Illuminate\Support\Facades\DB::raw('false');
echo "Value after assignment: ";
var_dump($case->is_marketplace);

$case->is_marketplace = false;
echo "Value after false assignment: ";
var_dump($case->is_marketplace);
