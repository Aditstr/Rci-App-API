<?php

namespace Tests\Feature\Api;

use App\Models\LegalCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\ExpertProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CaseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $client1;
    private User $client2;
    private User $lawyer1;
    private User $lawyer2;
    private LegalCase $case1;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Notification::fake();

        $this->client1 = User::factory()->create(['role' => 'client']);
        $this->client2 = User::factory()->create(['role' => 'client']);
        
        $this->lawyer1 = User::factory()->create(['role' => 'lawyer']);
        ExpertProfile::create([
            'user_id' => $this->lawyer1->id,
            'license_number' => 'L-11111',
            'experience_years' => 5,
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $this->lawyer2 = User::factory()->create(['role' => 'lawyer']);
        ExpertProfile::create([
            'user_id' => $this->lawyer2->id,
            'license_number' => 'L-22222',
            'experience_years' => 5,
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        // Client 1's case, assigned to Lawyer 1
        $this->case1 = LegalCase::factory()->create([
            'client_id' => $this->client1->id,
            'expert_id' => $this->lawyer1->id,
            'proposed_fee' => 1000000,
            'quotation_status' => 'pending_client_approval',
        ]);
    }

    public function test_client_cannot_approve_or_reject_other_clients_quotation()
    {
        // Client 2 attempts to approve Client 1's case quotation
        $response = $this->actingAs($this->client2)->postJson("/api/v1/cases/{$this->case1->id}/quotation/approve");
        $response->assertStatus(403);

        // Client 2 attempts to reject Client 1's case quotation
        $response = $this->actingAs($this->client2)->postJson("/api/v1/cases/{$this->case1->id}/quotation/reject", [
            'reason' => 'Too high',
        ]);
        $response->assertStatus(403);
    }

    public function test_client_can_approve_own_case_quotation_with_sufficient_balance()
    {
        // Fund Client 1
        Wallet::firstOrCreate(
            ['user_id' => $this->client1->id],
            ['balance' => '2000000.00']
        );

        $response = $this->actingAs($this->client1)->postJson("/api/v1/cases/{$this->case1->id}/quotation/approve");
        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_uninvolved_lawyer_cannot_send_quotation()
    {
        // Lawyer 2 attempts to send quotation to Case 1 (assigned to Lawyer 1)
        $response = $this->actingAs($this->lawyer2)->postJson("/api/v1/lawyer/cases/{$this->case1->id}/quote", [
            'proposed_fee' => 1500000,
            'fee_notes' => 'New proposal',
        ]);
        
        $response->assertStatus(403);
    }

    public function test_uninvolved_user_cannot_upload_document()
    {
        // Client 2 attempts to upload document to Case 1
        $file = UploadedFile::fake()->create('document.pdf', 500);
        
        $response = $this->actingAs($this->client2)->postJson("/api/v1/cases/{$this->case1->id}/documents", [
            'file' => $file,
            'document_type' => 'identification',
        ]);

        $response->assertStatus(403);
    }
}
