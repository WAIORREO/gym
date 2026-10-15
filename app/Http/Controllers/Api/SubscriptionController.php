<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('user_id')) {
            return response()->json(
                Subscription::where('user_id', $request->get('user_id'))
                    ->with('user')
                    ->with('package')
                    ->with('package.cycle')
                    ->with('package.services')->get()
            );
        }

        $results = QueryBuilder::for(Subscription::class)
            ->allowedFilters(
                AllowedFilter::exact('package_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('status'),
            )
            ->with('user')
            ->with('package')
            ->with('package.cycle')
            ->with('package.services')
            ->paginate(request()->get('per_page', 15))
            ->appends(request()->query());

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSubscriptionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSubscriptionRequest $request)
    {
        return response()->json(app(\App\Services\MembershipService::class)->save($request->validated()));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function show(Subscription $subscription)
    {
        $subscription->load('user')->load('package');

        return response()->json($subscription);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSubscriptionRequest  $request
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        return response()->json(app(\App\Services\MembershipService::class)->save($request->validated(), $subscription));
    }

    /**
     * Renew an existing subscription.
     * Sets start date to today (or extends from previous expiry if still active),
     * calculates new end date based on duration, and resets sessions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function renew(Request $request, Subscription $subscription)
    {
        $data = $request->validate(['package_id'=>'sometimes|required|integer|exists:packages,id',
            'interval'=>'sometimes|required|integer|min:1|max:120','total_sessions'=>'nullable|integer|min:1|max:100000']);
        $renewed = app(\App\Services\MembershipService::class)->renew($subscription, $data);
        return response()->json(['message'=>__('membership.renewed'),'subscription'=>$renewed], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function transition(Request $request, Subscription $subscription)
    {
        $data = $request->validate(['action'=>'required|in:freeze,resume']);
        $expected = $data['action'] === 'freeze' ? 'active' : 'suspended';
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $subscription, $expected) {
            $subscription = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            abort_unless($subscription->effective_status === $expected, 422, __('membership.invalid_transition'));
            return response()->json(app(\App\Services\MembershipService::class)->save([
                'status'=>$data['action'] === 'freeze' ? 'suspended' : 'active'
            ], $subscription));
        });
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return response()->json(null, 204);
    }
}
