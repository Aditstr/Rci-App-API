<?php

namespace Tests\Feature\Api;

use App\Models\LegalCase;
use App\Models\User;
use App\Models\ExpertProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CaseEscalationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $paralegal1;
    private User $paralegal2;
    private User $lawyer;
    private User $unverifiedLawyer;
    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->client = User::factory()->create(['role' => 'client']);
        
        $this->paralegal1 = User::factory()->create(['role' => 'paralegal']);
        ExpertProfile::create([
            'user_id' => $this->paralegal1->id,
            'license_number' => 'P-11111',
            'experience_years' => 2,
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $this->paralegal2 = User::factory()->create(['role' => 'paralegal']);
        ExpertProfile::create([
            'user_id' => $this->paralegal2->id,
            'license_number' => 'P-22222',
            'experience_years' => 3,
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $this->lawyer = User::factory()->create(['role' => 'lawyer']);
        ExpertProfile::create([
            'user_id' => $this->lawyer->id,
            'license_number' => 'L-99999',
            'experience_years' => 7,
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $this->unverifiedLawyer = User::factory()->create(['role' => 'lawyer']);
        ExpertProfile::create([
            'user_id' => $this->unverifiedLawyer->id,
            'license_number' => 'L-88888',
            'experience_years' => 1,
            'verification_status' => 'pending',
            'is_verified' => false,
        ]);

        $this->case = LegalCase::factory()->create([
            'client_id' => $this->client->id,
            'expert_id' => $this->paralegal1->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_paralegal_can_escalate_own_case_to_verified_lawyer()
    {
        $response = $this->actingAs($this->paralegal1)
            ->postJson("/api/v1/paralegal/cases/{$this->case->id}/escalate", [
                'lawyer_id' => $this->lawyer->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'escalated')
            ->assertJsonPath('data.expert_id', $this->lawyer->id);

        $this->assertDatabaseHas('cases', [
            'id' => $this->case->id,
            'status' => 'escalated',
            'expert_id' => $this->lawyer->id,
        ]);

        // Assert notification sent to lawyer and client
        Notification::assertSentTo($this->lawyer, \App\Notifications\CaseStatusUpdated::class);
        Notification::assertSentTo($this->client, \App\Notifications\CaseStatusUpdated::class);
    }

    public function test_paralegal_cannot_escalate_case_assigned_to_someone_else()
    {
        $response = $this->actingAs($this->paralegal2)
            ->postJson("/api/v1/paralegal/cases/{$this->case->id}/escalate", [
                'lawyer_id' => $this->lawyer->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_non_paralegal_cannot_escalate_case()
    {
        $response = $this->actingAs($this->client)
            ->postJson("/api/v1/paralegal/cases/{$this->case->id}/escalate", [
                'lawyer_id' => $this->lawyer->id,
            ]);

        // Since client doesn't have paralegal role, they fail role:paralegal middleware with 403 or 401 depending on middleware
        $response->assertStatus(403);
    }

    public function test_paralegal_cannot_escalate_to_unverified_lawyer()
    {
        $response = $this->actingAs($this->paralegal1)
            ->postJson("/api/v1/paralegal/cases/{$this->case->id}/escalate", [
                'lawyer_id' => $this->unverifiedLawyer->id,
            ]);

        $response->assertStatus(404); // fails to find the lawyer matching verification criteria
    }

    public function test_paralegal_cannot_escalate_to_non_lawyer()
    {
        $response = $this->actingAs($this->paralegal1)
            ->postJson("/api/v1/paralegal/cases/{$this->case->id}/escalate", [
                'lawyer_id' => $this->client->id,
            ]);

        $response->assertStatus(404);
    }
}
