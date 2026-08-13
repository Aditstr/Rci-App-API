<?php

namespace Tests\Feature\Api;

use App\Models\ExpertProfile;
use App\Models\LegalCase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $paralegal;
    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create([
            'role'              => 'client',
            'email_verified_at' => now(),
        ]);

        $this->paralegal = User::factory()->create([
            'role'              => 'paralegal',
            'email_verified_at' => now(),
        ]);

        ExpertProfile::create([
            'user_id'             => $this->paralegal->id,
            'license_number'      => 'PARA-TEST-123',
            'verification_status' => 'approved',
            'is_verified'         => true,
            'rating'              => 0.00,
            'successful_cases_count' => 0,
        ]);

        $this->case = LegalCase::create([
            'case_number'  => LegalCase::generateCaseNumber(),
            'client_id'    => $this->client->id,
            'expert_id'    => $this->paralegal->id,
            'title'        => 'Kasus Review Test',
            'description'  => 'Test review.',
            'category'     => 'general',
            'status'       => 'completed', // Case must be completed to be reviewed
        ]);
    }

    public function test_client_can_leave_a_review_and_rating_updates()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->client);

        $response = $this->postJson("/api/v1/cases/{$this->case->id}/review", [
            'rating'  => 4,
            'comment' => 'Pekerjaan sangat bagus dan cepat.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 4);

        // Verify Review is saved
        $this->assertDatabaseHas('reviews', [
            'case_id'   => $this->case->id,
            'client_id' => $this->client->id,
            'expert_id' => $this->paralegal->id,
            'rating'    => 4,
            'comment'   => 'Pekerjaan sangat bagus dan cepat.',
        ]);

        // Verify ExpertProfile rating and successful_cases_count is updated
        $expertProfile = ExpertProfile::where('user_id', $this->paralegal->id)->first();
        
        $this->assertEquals(4.00, $expertProfile->rating);
        $this->assertEquals(1, $expertProfile->successful_cases_count);

        // Add a second completed case and review from another client to test average
        $client2 = User::factory()->create(['role' => 'client', 'email_verified_at' => now()]);
        $case2 = LegalCase::create([
            'case_number'  => LegalCase::generateCaseNumber(),
            'client_id'    => $client2->id,
            'expert_id'    => $this->paralegal->id,
            'title'        => 'Kasus Review 2',
            'description'  => 'Test.',
            'category'     => 'general',
            'status'       => 'completed',
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($client2);
        $this->postJson("/api/v1/cases/{$case2->id}/review", [
            'rating'  => 5,
            'comment' => 'Sempurna!',
        ])->assertCreated();

        // Average of 4 and 5 is 4.5
        $expertProfile->refresh();
        $this->assertEquals(4.50, $expertProfile->rating);
        $this->assertEquals(2, $expertProfile->successful_cases_count);
    }

    public function test_cannot_review_uncompleted_case()
    {
        $this->case->update(['status' => 'in_progress']);

        \Laravel\Sanctum\Sanctum::actingAs($this->client);

        $response = $this->postJson("/api/v1/cases/{$this->case->id}/review", [
            'rating'  => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Review hanya dapat diberikan untuk kasus yang telah selesai.');
    }

    public function test_cannot_review_twice()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->client);

        $this->postJson("/api/v1/cases/{$this->case->id}/review", [
            'rating'  => 5,
        ])->assertCreated();

        $response = $this->postJson("/api/v1/cases/{$this->case->id}/review", [
            'rating'  => 3,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Anda sudah memberikan review untuk kasus ini sebelumnya.');
    }

    public function test_only_client_of_the_case_can_review()
    {
        $otherClient = User::factory()->create(['role' => 'client', 'email_verified_at' => now()]);
        \Laravel\Sanctum\Sanctum::actingAs($otherClient);

        $response = $this->postJson("/api/v1/cases/{$this->case->id}/review", [
            'rating'  => 5,
        ]);

        $response->assertForbidden();
    }
}
