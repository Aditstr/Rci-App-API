<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function mockWebhookToken(bool $valid): void
    {
        $this->mock(XenditService::class, function ($mock) use ($valid) {
            $mock->shouldReceive('verifyWebhookToken')->andReturn($valid);
        });
    }

    private function pendingPayment(User $user, string $invoiceId = 'inv-1', string $amount = '50000.00'): Payment
    {
        Wallet::create(['user_id' => $user->id, 'balance' => '0.00']);

        return Payment::create([
            'user_id' => $user->id,
            'payment_type' => 'topup',
            'amount' => $amount,
            'currency' => 'IDR',
            'status' => 'pending',
            'xendit_invoice_id' => $invoiceId,
            'payment_gateway_ref' => "TOPUP-{$user->id}",
        ]);
    }

    public function test_invalid_callback_token_is_rejected_without_crediting(): void
    {
        $this->mockWebhookToken(false);

        $user = User::factory()->create(['role' => 'client']);
        $payment = $this->pendingPayment($user);

        $this->postJson('/api/v1/xendit/webhook/invoice', [
            'id' => 'inv-1',
            'external_id' => 'TOPUP-1',
            'status' => 'PAID',
            'paid_amount' => 50000,
        ], ['x-callback-token' => 'wrong'])
            ->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('0.00', Wallet::firstOrFail()->balance);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_paid_callback_credits_wallet_once(): void
    {
        $this->mockWebhookToken(true);

        $user = User::factory()->create(['role' => 'client']);
        $payment = $this->pendingPayment($user);

        $payload = [
            'id' => 'inv-1',
            'external_id' => 'TOPUP-1',
            'status' => 'PAID',
            'paid_amount' => 50000,
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
            'bank_code' => 'BCA',
        ];

        $this->postJson('/api/v1/xendit/webhook/invoice', $payload, ['x-callback-token' => 'valid'])
            ->assertOk();

        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('50000.00', Wallet::firstOrFail()->balance);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertSame('deposit', WalletTransaction::firstOrFail()->type);

        // Xendit retries the same callback: balance must not be credited twice.
        $this->postJson('/api/v1/xendit/webhook/invoice', $payload, ['x-callback-token' => 'valid'])
            ->assertOk()
            ->assertJsonPath('message', 'Already processed');

        $this->assertSame('50000.00', Wallet::firstOrFail()->balance);
        $this->assertSame(1, WalletTransaction::count());
    }

    public function test_expired_callback_marks_payment_failed_without_crediting(): void
    {
        $this->mockWebhookToken(true);

        $user = User::factory()->create(['role' => 'client']);
        $payment = $this->pendingPayment($user);

        $this->postJson('/api/v1/xendit/webhook/invoice', [
            'id' => 'inv-1',
            'external_id' => 'TOPUP-1',
            'status' => 'EXPIRED',
        ], ['x-callback-token' => 'valid'])
            ->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('0.00', Wallet::firstOrFail()->balance);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_unknown_invoice_is_acknowledged_without_side_effects(): void
    {
        $this->mockWebhookToken(true);

        $this->postJson('/api/v1/xendit/webhook/invoice', [
            'id' => 'inv-unknown',
            'external_id' => 'TOPUP-unknown',
            'status' => 'PAID',
        ], ['x-callback-token' => 'valid'])
            ->assertOk()
            ->assertJsonPath('message', 'Payment not found, acknowledged');

        $this->assertSame(0, WalletTransaction::count());
    }
}
