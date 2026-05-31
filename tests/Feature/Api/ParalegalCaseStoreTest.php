<?php

namespace Tests\Feature\Api;

use App\Models\LegalCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParalegalCaseStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $paralegal;
    private User $normalClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paralegal = User::factory()->create([
            'role' => 'paralegal',
            'email_verified_at' => now(),
        ]);

        $this->normalClient = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
    }

    public function test_paralegal_can_store_case_for_client_and_funds_are_locked_from_paralegal_wallet()
    {
        // 1. Give the paralegal some wallet funds
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->paralegal->id],
            ['balance' => '500000.00']
        );

        // 2. Submit a new case on behalf of a village client
        $response = $this->actingAs($this->paralegal)->postJson('/api/v1/paralegal/cases', [
            'client_name'  => 'Pak Budi Desa',
            'client_phone' => '08123456789',
            'title'        => 'Sengketa Batas Tanah Sawah',
            'description'  => 'Terjadi sengketa batas tanah sawah dengan tetangga sebelah.',
            'category'     => 'hukum perdata',
            'amount'       => 200000,
        ]);

        // 3. Assert success response
        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.case.title', 'Sengketa Batas Tanah Sawah')
                 ->assertJsonPath('data.case.status', 'active')
                 ->assertJsonPath('data.case.expert_id', $this->paralegal->id)
                 ->assertJsonPath('data.client.name', 'Pak Budi Desa')
                 ->assertJsonPath('data.client.phone', '08123456789');

        // 4. Assert client account was created automatically
        $client = User::where('phone', '08123456789')->first();
        $this->assertNotNull($client);
        $this->assertEquals('client', $client->role);

        // 5. Assert database records: wallet balance decremented
        $wallet->refresh();
        $this->assertEquals('300000.00', $wallet->balance);

        // 6. Assert transaction record
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'      => $wallet->id,
            'amount'         => '200000.00',
            'type'           => 'escrow_hold',
            'reference_type' => LegalCase::class,
            'status'         => 'pending',
        ]);
    }

    public function test_paralegal_cannot_store_case_if_wallet_balance_is_insufficient()
    {
        // Give the paralegal insufficient funds
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->paralegal->id],
            ['balance' => '50000.00']
        );

        $response = $this->actingAs($this->paralegal)->postJson('/api/v1/paralegal/cases', [
            'client_name'  => 'Pak Budi Desa',
            'client_phone' => '08123456789',
            'title'        => 'Sengketa Sawah',
            'description'  => 'Sengketa batas sawah.',
            'category'     => 'hukum perdata',
            'amount'       => 200000, // Needs 200k, only has 50k
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('message', 'Saldo tidak mencukupi. Saldo saat ini: Rp 50.000, dibutuhkan: Rp 200.000.');
    }

    public function test_non_paralegal_cannot_store_case()
    {
        $response = $this->actingAs($this->normalClient)->postJson('/api/v1/paralegal/cases', [
            'client_name'  => 'Pak Budi Desa',
            'client_phone' => '08123456789',
            'title'        => 'Sengketa Sawah',
            'description'  => 'Sengketa batas sawah.',
            'category'     => 'hukum perdata',
            'amount'       => 200000,
        ]);

        $response->assertStatus(403);
    }
}
