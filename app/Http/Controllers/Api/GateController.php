<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Constants\StatusType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GateController extends Controller
{
    /**
     * Handle RFID / Turnstile card swipe for gym access.
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'rfid_card_id' => 'required_without:account_number|string',
            'account_number' => 'nullable|string',
            'branch_id' => 'nullable|integer'
        ]);

        $rfid = trim((string) ($request->input('rfid_card_id') ?? $request->input('account_number')));

        if (empty($rfid)) {
            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'INVALID_INPUT',
                'message' => 'يرجى تمرير بطاقة RFID أو إدخال رقم العضو',
                'message_en' => 'Please scan an RFID card or enter member number'
            ], 422);
        }

        // 1. Find user by RFID card ID, or fallback to account number
        $user = User::where('rfid_card_id', $rfid)
            ->orWhere('account_number', $rfid)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'CARD_NOT_FOUND',
                'message' => 'البطاقة غير مسجلة في النظام! يرجى مراجعة موظف الاستقبال',
                'message_en' => 'RFID Card not registered. Please see reception',
                'scanned_code' => $rfid
            ], 404);
        }

        // 2. Check Member Active Status
        if ($user->status !== StatusType::ACTIVE) {
            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'USER_INACTIVE',
                'message' => 'حساب العضو غير نشط أو موقوف! يرجى مراجعة إدارة النادي',
                'message_en' => 'Member account is suspended or inactive',
                'member' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'account_number' => $user->account_number,
                    'status' => $user->status
                ]
            ], 403);
        }

        // 3. Check Active Subscription
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            $latest = $user->latestSubscription();
            $expiredDate = ($latest && $latest->expires_at) 
                ? Carbon::parse($latest->expires_at)->format('Y-m-d')
                : null;

            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'SUBSCRIPTION_EXPIRED',
                'message' => $expiredDate 
                    ? "اشتراكك منتهي منذ ($expiredDate)، يرجى التجديد للاستمرار"
                    : 'لا يوجد اشتراك نشط لهذا العضو، يرجى مراجعة الاستقبال',
                'message_en' => 'Subscription has expired or is not active',
                'member' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'account_number' => $user->account_number,
                    'status' => $user->status
                ],
                'last_subscription' => $latest ? [
                    'package_name' => $latest->package ? $latest->package->name : 'باقة سابقة',
                    'expires_at' => $expiredDate
                ] : null
            ], 403);
        }

        // Resolve branch
        $branchId = $request->input('branch_id');
        $branch = $branchId ? Branch::find($branchId) : Branch::first();
        if (!$branch) {
            $branch = Branch::create(['name' => 'الفرع الرئيسي']);
        }
        $branchId = $branch->id;

        // 4. Anti-passback check: Did user check in within the last 2 minutes?
        $recentCheckIn = Attendance::where('user_id', $user->id)
            ->where('checked_in_at', '>=', now()->subMinutes(2))
            ->latest('checked_in_at')
            ->first();

        $alreadyCheckedIn = false;
        if ($recentCheckIn) {
            $alreadyCheckedIn = true;
        } else {
            // Record new Attendance
            Attendance::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'checked_in_at' => now(),
            ]);

            // Record activity log
            Activity::create([
                'user_id' => $user->id,
                'type' => 'attendance',
                'entity' => 'user',
                'description' => 'تسجيل دخول عبر بوابة RFID (Gate Entry)'
            ]);
        }

        // Calculate remaining days
        $daysLeft = null;
        if ($subscription->expires_at) {
            $expiresAt = Carbon::parse($subscription->expires_at);
            $daysLeft = (int) ceil(now()->diffInDays($expiresAt, false));
            if ($daysLeft < 0) {
                $daysLeft = 0;
            }
        }

        return response()->json([
            'success' => true,
            'access_granted' => true,
            'status_code' => 'ACCESS_GRANTED',
            'message' => $alreadyCheckedIn 
                ? 'تم تسجيل الدخول بالفعل منذ لحظات، تفضل بالدخول!'
                : 'تم التحقق بنجاح، تفضل بالدخول وتمرين موفق!',
            'message_en' => 'Access Granted! Enjoy your workout!',
            'already_checked_in' => $alreadyCheckedIn,
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'account_number' => $user->account_number,
                'rfid_card_id' => $user->rfid_card_id,
                'status' => $user->status
            ],
            'subscription' => [
                'id' => $subscription->id,
                'package_name' => $subscription->package ? $subscription->package->name : 'باقة عامة',
                'cycle_name' => $subscription->cycle ? $subscription->cycle->name : 'شهري',
                'expires_at' => $subscription->expires_at ? Carbon::parse($subscription->expires_at)->format('Y-m-d') : 'مفتوح',
                'days_left' => $daysLeft,
                'is_expiring_soon' => ($daysLeft !== null && $daysLeft <= 5)
            ],
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name
            ] : null,
            'checked_in_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get recent gate check-ins (e.g. today's live feed).
     */
    public function recent(Request $request)
    {
        $limit = (int) $request->input('limit', 15);
        $branchId = $request->input('branch_id');

        $query = Attendance::with(['user', 'branch'])
            ->whereDate('checked_in_at', now()->today())
            ->orderBy('checked_in_at', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->take($limit)->get();

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'data' => $items
        ]);
    }

    /**
     * Get gate statistics for today.
     */
    public function stats(Request $request)
    {
        $today = now()->today();

        $todayCount = Attendance::whereDate('checked_in_at', $today)->count();
        $lastHourCount = Attendance::where('checked_in_at', '>=', now()->subHour())->count();
        $totalRegisteredMembers = User::where('is_admin', false)->count();

        return response()->json([
            'today_checkins' => $todayCount,
            'last_hour_checkins' => $lastHourCount,
            'total_members' => $totalRegisteredMembers
        ]);
    }
}
