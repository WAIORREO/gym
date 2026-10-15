<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cycle;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UserSessionTrait;

class GateTest extends TestCase
{
    use RefreshDatabase, UserSessionTrait;

    public function test_can_check_in_with_valid_rfid()
    {
        $bearer = $this->getAdminAuth();

        $branch = Branch::create(['name' => 'Main Gate Branch']);
        $cycle = Cycle::create(['name' => 'Monthly', 'days' => 30]);
        $package = Package::create(['name' => 'Gold Package', 'cycle_id' => $cycle->id, 'description' => 'Gold', 'amount' => 100]);

        $member = User::create([
            'name' => 'Sami Ahmed',
            'email' => 'sami@example.com',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'CARD_RFID_9988',
            'status' => 'active',
            'is_admin' => false
        ]);

        Subscription::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1,
            'status' => 'active',
            'expires_at' => now()->addDays(30)
        ]);

        $response = $this->postJson('/api/gate/check-in', [
            'rfid_card_id' => 'CARD_RFID_9988',
            'branch_id' => $branch->id
        ], [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'access_granted' => true,
            'status_code' => 'ACCESS_GRANTED',
            'member' => [
                'id' => $member->id,
                'name' => 'Sami Ahmed',
                'rfid_card_id' => 'CARD_RFID_9988'
            ]
        ]);
    }

    public function test_fails_with_invalid_rfid()
    {
        $bearer = $this->getAdminAuth();

        $response = $this->postJson('/api/gate/check-in', [
            'rfid_card_id' => 'NON_EXISTENT_CARD_999'
        ], [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'access_granted' => false,
            'status_code' => 'CARD_NOT_FOUND'
        ]);
    }

    public function test_fails_when_subscription_is_expired()
    {
        $bearer = $this->getAdminAuth();

        $cycle = Cycle::create(['name' => 'Monthly', 'days' => 30]);
        $package = Package::create(['name' => 'Gold Package', 'cycle_id' => $cycle->id, 'description' => 'Gold', 'amount' => 100]);

        $member = User::create([
            'name' => 'Omar Ali',
            'email' => 'omar@example.com',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'CARD_EXPIRED_1122',
            'status' => 'active',
            'is_admin' => false
        ]);

        Subscription::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1,
            'status' => 'active',
            'expires_at' => now()->subDays(5) // Expired 5 days ago
        ]);

        $response = $this->postJson('/api/gate/check-in', [
            'rfid_card_id' => 'CARD_EXPIRED_1122'
        ], [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'access_granted' => false,
            'status_code' => 'SUBSCRIPTION_EXPIRED'
        ]);
    }

    public function test_fails_when_sessions_are_depleted()
    {
        $bearer = $this->getAdminAuth();

        $cycle = Cycle::create(['name' => 'Monthly', 'days' => 30]);
        $package = Package::create(['name' => 'Session Package', 'cycle_id' => $cycle->id, 'description' => 'Test', 'amount' => 50, 'sessions' => 10]);

        $member = User::create([
            'name' => 'Yassine Depleted',
            'email' => 'depleted@example.com',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'CARD_DEPLETED_7788',
            'status' => 'active',
            'is_admin' => false
        ]);

        Subscription::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1,
            'total_sessions' => 10,
            'remaining_sessions' => 0, // Depleted!
            'sessions_used' => 10,
            'status' => 'completed',
            'expires_at' => now()->addDays(20)
        ]);

        $response = $this->postJson('/api/gate/check-in', [
            'rfid_card_id' => 'CARD_DEPLETED_7788'
        ], [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'access_granted' => false,
            'status_code' => 'SESSIONS_DEPLETED'
        ]);
    }

    public function test_decrements_remaining_sessions_on_check_in()
    {
        $bearer = $this->getAdminAuth();

        $cycle = Cycle::create(['name' => 'Monthly', 'days' => 30]);
        $package = Package::create(['name' => '10 Sessions Pack', 'cycle_id' => $cycle->id, 'description' => 'Test', 'amount' => 50, 'sessions' => 10]);

        $member = User::create([
            'name' => 'Karim Active',
            'email' => 'karim@example.com',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'CARD_KARIM_123',
            'status' => 'active',
            'is_admin' => false
        ]);

        $sub = Subscription::create([
            'user_id' => $member->id,
            'package_id' => $package->id,
            'interval' => 1,
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'sessions_used' => 0,
            'status' => 'active',
            'expires_at' => now()->addDays(20)
        ]);

        $response = $this->postJson('/api/gate/check-in', [
            'rfid_card_id' => 'CARD_KARIM_123'
        ], [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'access_granted' => true,
            'subscription' => [
                'total_sessions' => 10,
                'remaining_sessions' => 9,
                'sessions_used' => 1
            ]
        ]);

        $this->assertEquals(9, $sub->fresh()->remaining_sessions);
        $this->assertEquals(1, $sub->fresh()->sessions_used);
    }

    public function test_can_get_recent_gate_entries()
    {
        $bearer = $this->getAdminAuth();

        $response = $this->getJson('/api/gate/recent', [
            'Authorization' => $bearer
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'count',
            'data'
        ]);
    }
}
