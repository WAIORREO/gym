<?php
namespace App\Services;

use App\Models\{Subscription, Package, User, Activity};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function save(array $data, ?Subscription $subscription = null): Subscription
    {
        return DB::transaction(function () use ($data, $subscription) {
            if ($subscription) $subscription = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            $package = Package::findOrFail($data['package_id'] ?? $subscription->package_id);
            $interval = (int) ($data['interval'] ?? $subscription->interval ?? 1);
            $start = Carbon::parse($data['starts_at'] ?? $subscription->starts_at ?? now())->startOfDay();
            $expiry = isset($data['expires_at']) ? Carbon::parse($data['expires_at'])->endOfDay()
                : ($subscription && $package->id === $subscription->package_id && !isset($data['starts_at']) && !isset($data['interval'])
                    ? $subscription->expires_at->copy() : $start->copy()->addDays(max(1, (int) ($package->cycle->num_days ?? 30)) * $interval - 1)->endOfDay());
            if ($expiry->lt($start)) throw ValidationException::withMessages(['expires_at' => __('membership.invalid_dates')]);
            $total = array_key_exists('total_sessions', $data) ? $data['total_sessions']
                : ($subscription ? $subscription->total_sessions : ($package->sessions ? $package->sessions * $interval : null));
            $used = (int) ($data['sessions_used'] ?? $subscription->sessions_used ?? 0);
            $remaining = array_key_exists('remaining_sessions', $data) ? $data['remaining_sessions']
                : ($subscription ? $subscription->remaining_sessions : $total);
            if ($total !== null && ($total < 1 || $remaining === null || $remaining < 0 || $used < 0 || $remaining + $used !== (int) $total)) {
                throw ValidationException::withMessages(['remaining_sessions' => __('membership.invalid_sessions')]);
            }
            if ($total === null) $remaining = null;
            $status = $data['status'] ?? $subscription->status ?? 'active';
            $suspendedAt = $subscription?->frozen_at;
            if ($status === 'suspended' && (!$subscription || $subscription->status !== 'suspended')) $suspendedAt = now();
            if ($subscription && $subscription->status === 'suspended' && $status === 'active') {
                if ($subscription->frozen_at) $expiry->addSeconds(max(0, $subscription->frozen_at->diffInSeconds(now(), false)));
                $suspendedAt = null;
            }
            if ($status === 'active' && $total !== null && $remaining === 0) $status = 'completed';
            $values = ['package_id'=>$package->id, 'user_id'=>$data['user_id'] ?? $subscription->user_id,
                'interval'=>$interval, 'starts_at'=>$start, 'expires_at'=>$expiry, 'total_sessions'=>$total,
                'remaining_sessions'=>$remaining, 'sessions_used'=>$used, 'status'=>$status, 'frozen_at'=>$suspendedAt];
            if ($subscription) $subscription->update($values);
            else $subscription = Subscription::create($values);
            Activity::create(['entity_id'=>$subscription->user_id,'type'=>'membership','description'=>'Membership #'.$subscription->id.' '.$status.'; operator #'.auth()->id()]);
            return $subscription->fresh(['package.cycle','package.services','user']);
        });
    }

    public function renew(Subscription $subscription, array $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            User::whereKey($subscription->user_id)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (Subscription::withTrashed()->where('renewed_from_id',$subscription->id)->exists()) {
                throw ValidationException::withMessages(['subscription'=>__('membership.already_renewed')]);
            }
            if (in_array($subscription->status,['suspended','cancelled'])) {
                throw ValidationException::withMessages(['subscription'=>__('membership.cannot_renew')]);
            }
            $start = $subscription->effective_status === 'active' || $subscription->effective_status === 'scheduled'
                ? $subscription->expires_at->copy()->addDay()->startOfDay() : now()->startOfDay();
            $renewed = $this->save(array_merge($data,['user_id'=>$subscription->user_id,
                'package_id'=>$data['package_id'] ?? $subscription->package_id,
                'interval'=>$data['interval'] ?? $subscription->interval,'starts_at'=>$start,'status'=>'active']));
            $renewed->update(['renewed_from_id'=>$subscription->id]);
            return $renewed;
        });
    }
}
