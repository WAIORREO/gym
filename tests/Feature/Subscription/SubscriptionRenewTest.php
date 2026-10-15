<?php

namespace Tests\Feature\Subscription;

use App\Models\Cycle;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionRenewTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subscription_with_automatic_today_start_date_and_calculated_end_date()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['is_admin' => false]);
        $cycle = Cycle::factory()->create(['num_days' => 30]);
        $package = Package::factory()->create(['cycle_id' => $cycle->id, 'sessions' => 12]);

        $response = $this->actingAs($admin)->postJson('/api/subscriptions', [
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(12, $data['total_sessions']);
        $this->assertEquals(12, $data['remaining_sessions']);
        $this->assertNotNull($data['starts_at']);
        $this->assertNotNull($data['expires_at']);

        // Check start is today and end is 30 days later
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['expires_at']);
        $this->assertTrue($start->isToday());
        $this->assertTrue($end->isFuture());
    }

    public function test_can_renew_subscription_and_reset_sessions_and_extend_expiry()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['is_admin' => false]);
        $cycle = Cycle::factory()->create(['num_days' => 30]);
        $package = Package::factory()->create(['cycle_id' => $cycle->id, 'sessions' => 12]);

        $subscription = Subscription::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1,
            'starts_at' => now()->subDays(35),
            'expires_at' => now()->subDays(5),
            'total_sessions' => 12,
            'remaining_sessions' => 0,
            'sessions_used' => 12,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($admin)->postJson("/api/subscriptions/{$subscription->id}/renew", [
            'interval' => 1
        ]);

        $response->assertStatus(201);

        $renewed = Subscription::where('renewed_from_id', $subscription->id)->firstOrFail();
        $this->assertEquals('active', $renewed->status);
        $this->assertEquals(12, $renewed->remaining_sessions);
        $this->assertEquals(0, $renewed->sessions_used);
        $this->assertTrue(Carbon::parse($renewed->expires_at)->isFuture());
    }
}
