<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualTopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('s3');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'amount' => 50000,
            'bank_name' => 'BCA',
            'sender_name' => 'Budi',
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ], $overrides);
    }

    public function test_guest_cannot_submit_manual_topup(): void
    {
        $this->postJson('/api/v1/rci/topup/manual', $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_amount_below_minimum_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->postJson('/api/v1/rci/topup/manual', $this->payload(['amount' => 9999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_amount_above_maximum_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->postJson('/api/v1/rci/topup/manual', $this->payload(['amount' => 100000001]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_proof_must_be_an_image_or_pdf(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->postJson('/api/v1/rci/topup/manual', $this->payload([
                'proof' => UploadedFile::fake()->create('bukti.exe', 10),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proof');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_valid_submission_stays_pending_and_does_not_credit_balance(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->postJson('/api/v1/rci/topup/manual', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_proof')
            ->assertJsonPath('data.amount', '50000.00');

        $payment = Payment::firstOrFail();

        $this->assertSame('topup', $payment->payment_type);
        $this->assertSame('manual_transfer', $payment->payment_method);
        $this->assertSame('pending_proof', $payment->status);
        $this->assertSame('50000.00', $payment->amount);
        $this->assertNotNull($payment->proof_path);
        $this->assertTrue(Storage::disk('local')->exists($payment->proof_path));

        $this->assertSame('0.00', Wallet::firstOrFail()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_user_only_lists_their_own_manual_topups(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $other = User::factory()->create(['role' => 'client']);

        Payment::create([
            'user_id' => $user->id,
            'payment_type' => 'topup',
            'amount' => '50000.00',
            'currency' => 'IDR',
            'status' => 'pending_proof',
            'payment_method' => 'manual_transfer',
            'bank_name' => 'BCA',
            'sender_name' => 'Budi',
        ]);

        Payment::create([
            'user_id' => $other->id,
            'payment_type' => 'topup',
            'amount' => '75000.00',
            'currency' => 'IDR',
            'status' => 'pending_proof',
            'payment_method' => 'manual_transfer',
            'bank_name' => 'BNI',
            'sender_name' => 'Siti',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/rci/topup/manual')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.amount', '50000.00')
            ->assertJsonPath('data.data.0.sender_name', 'Budi');
    }
}
