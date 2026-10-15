<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 9/13/2018
 * Time: 11:21 PM
 */

namespace App\Http\Controllers\Api;


use App\CacheKey;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * @return mixed
     */
    public function services()
    {
        $count = Cache::remember(CacheKey::get(), 15, function () {
            return Service::where('status', 'active')->count();
        });

        return response()->json($count);
    }

    public function packages()
    {
        $count = Cache::remember(CacheKey::get(), 15, function () {
            return Package::where('status', 'active')->count();
        });

        return response()->json($count);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function members()
    {
        $count = Cache::remember(CacheKey::get(), 15, function () {
            return User::where('is_admin', false)->count();
        });

        return response()->json($count);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscriptions()
    {
        $count = Cache::remember(CacheKey::get(), 15, function () {
            return Subscription::eligible()->count();
        });

        return response()->json($count);
    }

    public function overview()
    {
        $eligible = Subscription::eligible();
        return response()->json([
            'active_members'=> (clone $eligible)->distinct()->count('user_id'),
            'expiring_soon'=> (clone $eligible)->where('expires_at','<=',now()->addDays(7)->endOfDay())->count(),
            'low_sessions'=> (clone $eligible)->whereNotNull('total_sessions')->where('remaining_sessions','<=',2)->count(),
            'today_visits'=> \App\Models\Attendance::where('checked_in_at','>=',now()->startOfDay())->count(),
            'attention'=> (clone $eligible)->with(['user','package'])->where(function ($q) {
                $q->where('expires_at','<=',now()->addDays(7)->endOfDay())
                  ->orWhere(fn ($q)=>$q->whereNotNull('total_sessions')->where('remaining_sessions','<=',2));
            })->orderBy('expires_at')->limit(8)->get(),
            'recent'=> \App\Models\Attendance::with(['user','branch'])->latest('checked_in_at')->limit(6)->get(),
        ]);
    }
}
