<?php

namespace Tests\Feature;

use App\Filament\Resources\FeedbackResource\Pages\EditFeedback;
use App\Models\Feedback;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function feedback(): Feedback
    {
        return Feedback::create([
            'category' => 'saran',
            'message' => 'Perjelas petunjuk pada halaman konsultasi.',
            'email' => 'masukan@example.com',
        ]);
    }

    public function test_admin_can_read_feedback_and_update_only_follow_up_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = $this->feedback();
        $this->actingAs($admin)->get('/admin/kritik-saran')->assertOk();
        $this->get('/admin/kritik-saran/'.$feedback->id)->assertOk()->assertSee($feedback->message);

        Livewire::test(EditFeedback::class, ['record' => $feedback->id])
            ->fillForm(['status' => 'ditinjau', 'admin_notes' => 'Sedang ditinjau oleh tim.'])
            ->set('data.message', 'Isi masukan tidak boleh ditimpa.')
            ->set('data.email', 'diubah@example.com')
            ->call('save')->assertHasNoFormErrors();

        $this->assertDatabaseHas('feedback', [
            'id' => $feedback->id,
            'message' => 'Perjelas petunjuk pada halaman konsultasi.',
            'email' => 'masukan@example.com',
            'status' => 'ditinjau',
            'admin_notes' => 'Sedang ditinjau oleh tim.',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertNotNull($feedback->fresh()->reviewed_at);
    }

    public function test_non_admin_and_inactive_admin_cannot_read_or_edit_feedback(): void
    {
        $feedback = $this->feedback();
        foreach ([['role' => 'client'], ['role' => 'admin', 'is_active' => false]] as $attributes) {
            $user = User::factory()->create($attributes);
            $this->actingAs($user)->get('/admin/kritik-saran')->assertForbidden();
            $this->get('/admin/kritik-saran/'.$feedback->id)->assertForbidden();
            $this->get('/admin/kritik-saran/'.$feedback->id.'/edit')->assertForbidden();
            $this->assertFalse(Gate::forUser($user)->allows('view', $feedback));
            $this->assertFalse(Gate::forUser($user)->allows('update', $feedback));
        }
    }

    public function test_admin_view_escapes_submitted_markup(): void
    {
        $feedback = $this->feedback();
        $message = 'Teks pengguna dengan <b>penanda</b> di dalamnya.';
        $feedback->update(['message' => $message]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/kritik-saran/'.$feedback->id)
            ->assertOk()->assertSee(e($message), false)->assertDontSee($message, false);
    }

    public function test_admin_must_choose_a_valid_follow_up_status(): void
    {
        $feedback = $this->feedback();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(EditFeedback::class, ['record' => $feedback->id])
            ->fillForm(['status' => 'status_tidak_valid'])
            ->call('save')->assertHasFormErrors(['status']);
        $this->assertSame('baru', $feedback->fresh()->status);
    }
}
