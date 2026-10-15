<?php
namespace Tests\Feature;
use App\Models\{User, Branch, Cycle, Package, Subscription, Attendance, Activity};
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\{TestCase, UserSessionTrait};
use Carbon\Carbon;
class MembershipLifecycleTest extends TestCase
{
    use RefreshDatabase, UserSessionTrait;
    private function membership($sessions = 2): array {
        $branch=Branch::create(['name'=>'Test branch']);
        $cycle=Cycle::create(['name'=>'30 days','num_days'=>30]);
        $package=Package::create(['name'=>'Training','cycle_id'=>$cycle->id,'amount'=>100,'sessions'=>$sessions]);
        $member=User::factory()->create(['status'=>'active','is_admin'=>false,'rfid_card_id'=>'TEST-CARD']);
        $member->branches()->attach($branch);
        $sub=app(MembershipService::class)->save(['user_id'=>$member->id,'package_id'=>$package->id]);
        return [$member,$branch,$sub,$package];
    }
    public function test_final_session_scan_is_idempotent_and_attached_to_membership() {
        [$u,$b,$s]=$this->membership(1); $auth=['Authorization'=>$this->getAdminAuth()];
        $body=['rfid_card_id'=>$u->rfid_card_id,'branch_id'=>$b->id];
        $this->postJson('/api/gate/check-in',$body,$auth)->assertOk()->assertJsonPath('already_checked_in',false);
        $this->postJson('/api/gate/check-in',$body,$auth)->assertOk()->assertJsonPath('already_checked_in',true);
        $this->assertEquals(1,Attendance::count());
        $this->assertEquals($s->id,Attendance::first()->subscription_id);
        $this->assertEquals(0,$s->fresh()->remaining_sessions);
        $this->assertEquals(1,$s->fresh()->sessions_used);
    }
    public function test_branch_membership_is_enforced() {
        [$u,$b,$s]=$this->membership(); $u->branches()->detach();
        $this->postJson('/api/gate/check-in',['rfid_card_id'=>$u->rfid_card_id,'branch_id'=>$b->id],['Authorization'=>$this->getAdminAuth()])
            ->assertForbidden()->assertJsonPath('status_code','BRANCH_NOT_ALLOWED');
        $this->assertEquals(0,Attendance::count());
        $this->assertEquals(2,$s->fresh()->remaining_sessions);
    }
    public function test_invalid_session_balances_and_empty_status_are_rejected() {
        [$u,$b,$s]=$this->membership(); $auth=['Authorization'=>$this->getAdminAuth()];
        foreach ([['remaining_sessions'=>null],['remaining_sessions'=>10],['status'=>'']] as $payload) {
            $this->putJson('/api/subscriptions/'.$s->id,$payload,$auth)->assertStatus(422);
        }
        $this->assertEquals(2,$s->fresh()->remaining_sessions);
    }
    public function test_renewal_preserves_usage_and_schedules_a_new_membership() {
        [$u,$b,$s]=$this->membership(); $s->update(['remaining_sessions'=>1,'sessions_used'=>1]);
        $auth=['Authorization'=>$this->getAdminAuth()];
        $this->postJson('/api/subscriptions/'.$s->id.'/renew',[],$auth)->assertCreated();
        $next=Subscription::where('renewed_from_id',$s->id)->firstOrFail();
        $this->assertEquals('scheduled',$next->effective_status);
        $this->assertEquals(1,$s->fresh()->sessions_used);
        $this->assertEquals(1,$s->fresh()->remaining_sessions);
        $this->assertEquals($s->id,$u->activeSubscription()->id);
        $this->postJson('/api/subscriptions/'.$s->id.'/renew',[],$auth)->assertStatus(422);
        $this->assertEquals(2,Subscription::count());
    }
    public function test_freeze_and_resume_restore_the_frozen_duration() {
        [$u,$b,$s]=$this->membership(); $auth=['Authorization'=>$this->getAdminAuth()]; $end=$s->expires_at->copy();
        $this->postJson('/api/subscriptions/'.$s->id.'/transition',['action'=>'freeze'],$auth)->assertOk();
        $this->assertNull($u->activeSubscription());
        $this->travel(3)->days();
        $this->postJson('/api/subscriptions/'.$s->id.'/transition',['action'=>'resume'],$auth)->assertOk();
        $this->assertEquals($end->addDays(3)->toDateTimeString(),$s->fresh()->expires_at->toDateTimeString());
        $this->assertNull($s->fresh()->frozen_at);
        $this->travelBack();
    }
    public function test_package_change_is_saved_and_future_membership_cannot_enter() {
        [$u,$b,$s,$p]=$this->membership(); $other=$p->replicate(); $other->name='New'; $other->save();
        $auth=['Authorization'=>$this->getAdminAuth()];
        $this->putJson('/api/subscriptions/'.$s->id,['package_id'=>$other->id],$auth)->assertOk();
        $this->assertEquals($other->id,$s->fresh()->package_id);
        $this->putJson('/api/subscriptions/'.$s->id,['starts_at'=>now()->addDays(2)->toDateString()],$auth)->assertOk();
        $this->assertEquals('scheduled',$s->fresh()->effective_status);
        $this->assertNull($u->activeSubscription());
    }
    public function test_gate_rolls_back_when_audit_write_fails() {
        [$u,$b,$s]=$this->membership(); $auth=['Authorization'=>$this->getAdminAuth()];
        Activity::creating(function () { throw new \RuntimeException('Simulated failure'); });
        $this->postJson('/api/gate/check-in',['rfid_card_id'=>$u->rfid_card_id,'branch_id'=>$b->id],$auth)->assertStatus(500);
        $this->assertEquals(0,Attendance::count());
        $this->assertEquals(2,$s->fresh()->remaining_sessions);
    }
    public function test_null_remaining_legacy_membership_cannot_bypass_credit_check() {
        [$u,$b,$s]=$this->membership(); $s->update(['remaining_sessions'=>null]);
        $this->assertNull($u->activeSubscription());
    }
}
