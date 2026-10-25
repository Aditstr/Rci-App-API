<?php

namespace Tests\Feature;

use App\Filament\Pages\MembershipSettings;
use App\Filament\Resources\SettingResource\Pages\CreateSetting;
use App\Models\Setting;
use App\Models\User;
use App\Services\MembershipConfig;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_read_the_current_configuration(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '25000']);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/membership-settings')->assertOk();

        $this->assertTrue(MembershipSettings::canAccess());

        Livewire::test(MembershipSettings::class)->assertFormSet([
            'price' => 25000,
            'duration_days' => 30,
            'ai_daily_limit' => 3,
            'enabled' => true,
        ]);
    }

    public function test_opening_the_page_does_not_persist_defaults(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MembershipSettings::class)->assertOk();

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_admin_can_update_every_value(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MembershipSettings::class)
            ->fillForm([
                'price' => 30000,
                'duration_days' => 60,
                'ai_daily_limit' => 10,
                'enabled' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', ['key' => 'membership_price', 'value' => '30000']);
        $this->assertDatabaseHas('settings', ['key' => 'membership_duration_days', 'value' => '60']);
        $this->assertDatabaseHas('settings', ['key' => 'membership_ai_daily_limit', 'value' => '10']);
        $this->assertDatabaseHas('settings', ['key' => 'membership_enabled', 'value' => '0']);

        $this->assertSame(30000, MembershipConfig::price());
        $this->assertSame(60, MembershipConfig::durationDays());
        $this->assertSame(10, MembershipConfig::aiDailyLimit());
        $this->assertFalse(MembershipConfig::enabled());
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MembershipSettings::class)
            ->fillForm([
                'price' => 0,
                'duration_days' => 367,
                'ai_daily_limit' => -1,
            ])
            ->call('save')
            ->assertHasFormErrors(['price', 'duration_days', 'ai_daily_limit']);

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_non_admin_cannot_access_the_page(): void
    {
        foreach ([['role' => 'client'], ['role' => 'admin', 'is_active' => false]] as $attributes) {
            $user = User::factory()->create($attributes);

            $this->actingAs($user)->get('/admin/membership-settings')->assertForbidden();
        }

        $this->actingAs(User::factory()->create(['role' => 'client']));
        $this->assertFalse(MembershipSettings::canAccess());
    }

    public function test_membership_keys_are_hidden_from_the_generic_settings_resource(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '20000']);
        Setting::create(['key' => 'paralegal_sop', 'value' => '<p>SOP paralegal</p>']);

        $this->actingAs($this->admin())->get('/admin/settings')
            ->assertOk()
            ->assertSee('paralegal_sop')
            ->assertDontSee('membership_price');
    }

    public function test_membership_keys_cannot_be_created_through_the_generic_settings_resource(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateSetting::class)
            ->fillForm(['key' => 'membership_price', 'value' => '<p>Harga</p>'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        $this->assertDatabaseCount('settings', 0);
    }
}
