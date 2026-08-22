<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LegalCase;
use App\Services\EscrowService;
use App\Notifications\CaseStatusUpdated;
use Carbon\Carbon;

class AutoConfirmCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:auto-confirm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically confirm cases that have been completed by experts for more than 30 minutes';

    /**
     * Execute the console command.
     */
    public function handle(EscrowService $escrowService)
    {
        $cutoffTime = Carbon::now()->subMinutes(30);

        // Cari kasus yang statusnya awaiting_confirmation dan selesai lebih dari 30 menit yang lalu
        $cases = LegalCase::where('status', 'awaiting_confirmation')
            ->whereNotNull('expert_completed_at')
            ->where('expert_completed_at', '<=', $cutoffTime)
            ->get();

        $count = 0;

        foreach ($cases as $case) {
            $case->update([
                'status'              => 'completed',
                'completed_at'        => now(),
                'client_confirmed_at' => now(),
            ]);

            try {
                $escrowService->releaseFunds($case);

                // Notify the expert about completion + payment
                $case->load('expert');
                if ($case->expert) {
                    $case->expert->notify(new CaseStatusUpdated(
                        $case,
                        "Klien (secara otomatis) telah mengkonfirmasi penyelesaian kasus #{$case->case_number}. Dana telah dicairkan ke wallet Anda."
                    ));
                }
                
                $this->info("Case #{$case->case_number} confirmed automatically.");
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to release funds for case #{$case->case_number}: " . $e->getMessage());
            }
        }

        $this->info("Total {$count} cases auto-confirmed.");
    }
}
