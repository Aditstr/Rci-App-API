<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LegalCase;
use App\Models\ChatMessage;
use App\Http\Resources\ChatMessageResource;

try {
    $case = LegalCase::first();
    if (!$case) {
        die("No cases\n");
    }

    $messages = ChatMessage::with(['sender:id,name,role'])
        ->where('case_id', $case->id)
        ->orderBy('created_at', 'asc')
        ->cursorPaginate(50);

    $resource = ChatMessageResource::collection($messages)->response()->getData(true);
    print_r($resource);

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
