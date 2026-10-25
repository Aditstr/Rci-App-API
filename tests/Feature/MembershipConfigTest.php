<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\MembershipConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_used_when_settings_do_not_exist(): void
    {
        $this->assertSame([
            'price' => 20000,
            'duration_days' => 30,
            'ai_daily_limit' => 3,
            'enabled' => true,
        ], MembershipConfig::all());

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_stored_values_are_read(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '25000']);
        Setting::create(['key' => 'membership_duration_days', 'value' => '45']);
        Setting::create(['key' => 'membership_ai_daily_limit', 'value' => '7']);
        Setting::create(['key' => 'membership_enabled', 'value' => '0']);

        $this->assertSame(25000, MembershipConfig::price());
        $this->assertSame(45, MembershipConfig::durationDays());
        $this->assertSame(7, MembershipConfig::aiDailyLimit());
        $this->assertFalse(MembershipConfig::enabled());
    }

    public function test_invalid_stored_values_fall_back_to_defaults(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '0']);
        Setting::create(['key' => 'membership_duration_days', 'value' => '367']);
        Setting::create(['key' => 'membership_ai_daily_limit', 'value' => 'tiga']);
        Setting::create(['key' => 'membership_enabled', 'value' => 'mungkin']);

        $this->assertSame(20000, MembershipConfig::price());
        $this->assertSame(30, MembershipConfig::durationDays());
        $this->assertSame(3, MembershipConfig::aiDailyLimit());
        $this->assertTrue(MembershipConfig::enabled());
    }

    public function test_update_persists_values_and_refreshes_the_cache(): void
    {
        $this->assertSame(20000, MembershipConfig::price());

        MembershipConfig::update([
            'price' => 30000,
            'duration_days' => 60,
            'ai_daily_limit' => 10,
            'enabled' => false,
        ]);

        $this->assertSame(30000, MembershipConfig::price());
        $this->assertSame(60, MembershipConfig::durationDays());
        $this->assertSame(10, MembershipConfig::aiDailyLimit());
        $this->assertFalse(MembershipConfig::enabled());

        $this->assertDatabaseHas('settings', ['key' => 'membership_price', 'value' => '30000']);
        $this->assertDatabaseHas('settings', ['key' => 'membership_enabled', 'value' => '0']);
    }

    public function test_reading_never_writes_to_the_database(): void
    {
        Setting::create(['key' => 'membership_price', 'value' => '21000']);

        MembershipConfig::all();
        MembershipConfig::price();
        MembershipConfig::durationDays();
        MembershipConfig::aiDailyLimit();
        MembershipConfig::enabled();

        $this->assertDatabaseCount('settings', 1);
    }
}
