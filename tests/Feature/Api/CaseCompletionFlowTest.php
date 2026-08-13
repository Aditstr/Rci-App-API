<?php

namespace Tests\Feature\Api;

use App\Models\ExpertProfile;
use App\Models\LegalCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CaseCompletionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $paralegal;
    private User $admin;
    private string $clientToken;
    private string $paralegalToken;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Create admin (required for escrow release platform fee)
        $this->admin = User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $this->admin->id, 'balance' => 0]);

        // Create verified client with wallet
        $this->client = User::factory()->create([
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $this->client->id, 'balance' => 500000]);

        // Create verified paralegal with wallet and expert profile
        $this->paralegal = User::factory()->create([
            'role'              => 'paralegal',
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $this->paralegal->id, 'balance' => 0]);
        ExpertProfile::create([
            'user_id'             => $this->paralegal->id,
            'license_number'      => 'PARA-TEST-001',
            'verification_status' => 'approved',
            'is_verified'         => true,
        ]);

        $this->clientToken    = $this->client->createToken('test')->plainTextToken;
        $this->paralegalToken = $this->paralegal->createToken('test')->plainTextToken;
    }

    /**
     * Helper: Create an active case with escrow locked.
     */
    private function createActiveCaseWithEscrow(): LegalCase
    {
        $case = LegalCase::create([
            'case_number'  => LegalCase::generateCaseNumber(),
            'client_id'    => $this->client->id,
            'expert_id'    => $this->paralegal->id,
            'title'        => 'Kasus Perdata Test',
            'description'  => 'Deskripsi kasus test.',
            'category'     => 'general',
            'status'       => 'in_progress',
            'submitted_at' => now(),
            'assigned_at'  => now(),
        ]);

        // Simulate escrow lock (debit from client, record pending escrow)
        $clientWallet = Wallet::where('user_id', $this->client->id)->first();
        $clientWallet->debit('100000.00');

        WalletTransaction::create([
            'wallet_id'      => $clientWallet->id,
            'amount'         => '100000.00',
            'type'           => 'escrow_hold',
            'reference_id'   => $case->id,
            'reference_type' => LegalCase::class,
            'status'         => 'pending',
            'description'    => 'Escrow hold untuk kasus test',
        ]);

        return $case;
    }

    // ═══════════════════════════════════════════════════════
    // 1. Expert Marks Case as Done
    // ═══════════════════════════════════════════════════════

    public function test_expert_can_mark_case_as_completed(): void
    {
        $case = $this->createActiveCaseWithEscrow();

        $response = $this->postJson("/api/v1/expert/cases/{$case->id}/complete", [
            'completion_notes' => 'Kasus telah selesai ditangani. Semua dokumen sudah diproses.',
        ], [
            'Authorization' => "Bearer {$this->paralegalToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'awaiting_confirmation')
            ->assertJsonPath('data.completion_notes', 'Kasus telah selesai ditangani. Semua dokumen sudah diproses.');

        $case->refresh();
        $this->assertEquals('awaiting_confirmation', $case->status);
        $this->assertNotNull($case->expert_completed_at);
    }

    public function test_expert_completion_requires_notes(): void
    {
        $case = $this->createActiveCaseWithEscrow();

        $response = $this->postJson("/api/v1/expert/cases/{$case->id}/complete", [], [
            'Authorization' => "Bearer {$this->paralegalToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('completion_notes');
    }

    public function test_expert_cannot_complete_already_completed_case(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        $case->update(['status' => 'completed']);

        $response = $this->postJson("/api/v1/expert/cases/{$case->id}/complete", [
            'completion_notes' => 'Trying to complete again.',
        ], [
            'Authorization' => "Bearer {$this->paralegalToken}",
        ]);

        $response->assertForbidden();
    }

    public function test_client_cannot_mark_case_as_completed(): void
    {
        $case = $this->createActiveCaseWithEscrow();

        $response = $this->postJson("/api/v1/expert/cases/{$case->id}/complete", [
            'completion_notes' => 'Client trying to complete.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        // Client doesn't have expert.verified middleware, so they get 403
        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════
    // 2. Client Confirms Completion → Escrow Release
    // ═══════════════════════════════════════════════════════

    public function test_client_can_confirm_completion(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        $case->update([
            'status'              => 'awaiting_confirmation',
            'expert_completed_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/cases/{$case->id}/confirm-completion", [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $case->refresh();
        $this->assertEquals('completed', $case->status);
        $this->assertNotNull($case->completed_at);
        $this->assertNotNull($case->client_confirmed_at);

        // Verify escrow was released (90% to expert, 10% to admin)
        $expertWallet = Wallet::where('user_id', $this->paralegal->id)->first();
        $adminWallet  = Wallet::where('user_id', $this->admin->id)->first();

        // Expert should receive 90% of 100000 = 90000
        $this->assertEquals('90000.00', $expertWallet->balance);

        // Admin should receive 10% of 100000 = 10000
        $this->assertEquals('10000.00', $adminWallet->balance);

        // Escrow hold transaction should be marked as success
        $escrowTx = WalletTransaction::where('type', 'escrow_hold')
            ->where('reference_id', $case->id)
            ->first();
        $this->assertEquals('success', $escrowTx->status);
    }

    public function test_client_cannot_confirm_non_awaiting_case(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        // Case is still in_progress, not awaiting_confirmation

        $response = $this->postJson("/api/v1/cases/{$case->id}/confirm-completion", [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════
    // 3. Client Disputes Completion
    // ═══════════════════════════════════════════════════════

    public function test_client_can_dispute_case(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        $case->update([
            'status'              => 'awaiting_confirmation',
            'expert_completed_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/cases/{$case->id}/dispute", [
            'dispute_reason' => 'Hasil kerja tidak sesuai dengan deskripsi yang dijanjikan.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'dispute');

        $case->refresh();
        $this->assertEquals('dispute', $case->status);
        $this->assertEquals('Hasil kerja tidak sesuai dengan deskripsi yang dijanjikan.', $case->dispute_reason);

        // Escrow should still be pending (frozen for admin review)
        $escrowTx = WalletTransaction::where('type', 'escrow_hold')
            ->where('reference_id', $case->id)
            ->first();
        $this->assertEquals('pending', $escrowTx->status);
    }

    public function test_dispute_requires_reason(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        $case->update(['status' => 'awaiting_confirmation']);

        $response = $this->postJson("/api/v1/cases/{$case->id}/dispute", [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('dispute_reason');
    }

    public function test_client_cannot_dispute_in_progress_case(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        // Case is still in_progress

        $response = $this->postJson("/api/v1/cases/{$case->id}/dispute", [
            'dispute_reason' => 'Trying to dispute too early.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════
    // 4. Client Cancels Case → Refund
    // ═══════════════════════════════════════════════════════

    public function test_client_can_cancel_case_with_refund(): void
    {
        // Create a case in 'active' status (still cancellable) with escrow
        $case = LegalCase::create([
            'case_number'  => LegalCase::generateCaseNumber(),
            'client_id'    => $this->client->id,
            'expert_id'    => $this->paralegal->id,
            'title'        => 'Kasus untuk dibatalkan',
            'description'  => 'Test cancellation.',
            'category'     => 'general',
            'status'       => 'active',
            'submitted_at' => now(),
            'assigned_at'  => now(),
        ]);

        $clientWallet = Wallet::where('user_id', $this->client->id)->first();
        $balanceBefore = $clientWallet->balance;

        // Simulate escrow
        $clientWallet->debit('50000.00');
        WalletTransaction::create([
            'wallet_id'      => $clientWallet->id,
            'amount'         => '50000.00',
            'type'           => 'escrow_hold',
            'reference_id'   => $case->id,
            'reference_type' => LegalCase::class,
            'status'         => 'pending',
            'description'    => 'Test escrow',
        ]);

        $response = $this->postJson("/api/v1/cases/{$case->id}/cancel", [
            'cancellation_reason' => 'Saya sudah tidak membutuhkan layanan ini.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $case->refresh();
        $this->assertEquals('cancelled', $case->status);
        $this->assertEquals('Saya sudah tidak membutuhkan layanan ini.', $case->cancellation_reason);

        // Verify refund — balance should be restored
        $clientWallet->refresh();
        $this->assertEquals($balanceBefore, $clientWallet->balance);

        // Refund transaction should be recorded
        $refundTx = WalletTransaction::where('type', 'refund')
            ->where('reference_id', $case->id)
            ->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals('50000.00', $refundTx->amount);
    }

    public function test_client_cannot_cancel_in_progress_case(): void
    {
        $case = $this->createActiveCaseWithEscrow();
        // Case is in_progress — too late to cancel

        $response = $this->postJson("/api/v1/cases/{$case->id}/cancel", [
            'cancellation_reason' => 'Too late.',
        ], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertForbidden();
    }

    public function test_expert_cannot_cancel_case(): void
    {
        $case = LegalCase::create([
            'case_number'  => LegalCase::generateCaseNumber(),
            'client_id'    => $this->client->id,
            'expert_id'    => $this->paralegal->id,
            'title'        => 'Expert cancel test',
            'description'  => 'Test.',
            'category'     => 'general',
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/cases/{$case->id}/cancel", [
            'cancellation_reason' => 'Expert trying to cancel.',
        ], [
            'Authorization' => "Bearer {$this->paralegalToken}",
        ]);

        // Paralegal doesn't have client role middleware
        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════
    // Full Flow Integration Test
    // ═══════════════════════════════════════════════════════

    public function test_full_completion_flow_end_to_end(): void
    {
        // 1. Create an active case with locked escrow
        $case = $this->createActiveCaseWithEscrow();
        $this->assertEquals('in_progress', $case->status);

        // 2. Expert marks as done
        \Laravel\Sanctum\Sanctum::actingAs($this->paralegal);
        $this->postJson("/api/v1/expert/cases/{$case->id}/complete", [
            'completion_notes' => 'Semua dokumen telah diproses dan diserahkan.',
        ])->assertOk();

        $case->refresh();
        $this->assertEquals('awaiting_confirmation', $case->status);

        // 3. Client confirms
        \Laravel\Sanctum\Sanctum::actingAs($this->client);
        $response = $this->postJson("/api/v1/cases/{$case->id}/confirm-completion", []);
        
        $response->assertOk();

        $case->refresh();
        $this->assertEquals('completed', $case->status);
        $this->assertNotNull($case->completed_at);
        $this->assertNotNull($case->client_confirmed_at);

        // 4. Verify escrow distribution
        $expertWallet = Wallet::where('user_id', $this->paralegal->id)->first();
        $adminWallet  = Wallet::where('user_id', $this->admin->id)->first();

        $this->assertEquals('90000.00', $expertWallet->balance); // 90%
        $this->assertEquals('10000.00', $adminWallet->balance);  // 10%

        // 5. Verify transaction records
        $paymentRelease = WalletTransaction::where('type', 'payment_release')
            ->where('reference_id', $case->id)
            ->first();
        $this->assertNotNull($paymentRelease);
        $this->assertEquals('90000.00', $paymentRelease->amount);

        $adminFee = WalletTransaction::where('type', 'admin_fee')
            ->where('reference_id', $case->id)
            ->first();
        $this->assertNotNull($adminFee);
        $this->assertEquals('10000.00', $adminFee->amount);
    }
}
