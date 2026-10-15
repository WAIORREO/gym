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
            'branch_id' => 'nullable|integer|exists:branches,id'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
        $rfid = trim((string) ($request->input('rfid_card_id') ?? $request->input('account_number')));

        if (empty($rfid)) {
            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'INVALID_INPUT',
                'message' => 'يرجى تمرير بطاقة RFID أو إدخال رقم العضو',
                'message_ar' => 'يرجى تمرير بطاقة RFID أو إدخال رقم العضو',
                'message_fr' => 'Veuillez scanner une carte RFID ou saisir le numéro de membre',
                'message_en' => 'Please scan an RFID card or enter member number'
            ], 422);
        }

        // 1. Find user by RFID card ID, or fallback to account number
        $user = User::where('rfid_card_id', $rfid)
            ->orWhere('account_number', $rfid)
            ->lockForUpdate()->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => 'CARD_NOT_FOUND',
                'message' => 'البطاقة غير مسجلة في النظام! يرجى مراجعة موظف الاستقبال',
                'message_ar' => 'البطاقة غير مسجلة في النظام! يرجى مراجعة موظف الاستقبال',
                'message_fr' => 'Badge non enregistré dans le système ! Veuillez voir la réception',
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
                'message_ar' => 'حساب العضو غير نشط أو موقوف! يرجى مراجعة إدارة النادي',
                'message_fr' => 'Compte membre inactif ou suspendu ! Veuillez contacter la direction',
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

        // Lock the member before checking duplicates and consuming a membership.
        $branch = $request->filled('branch_id') ? Branch::find($request->branch_id) : Branch::first();
        if (!$branch || !$user->branches()->where('branches.id', $branch->id)->exists()) {
            return $this->denied('BRANCH_NOT_ALLOWED', 'branch_not_allowed');
        }
        $branchId = $branch->id;
        $recentCheckIn = Attendance::where('user_id', $user->id)->where('branch_id', $branchId)
            ->where('checked_in_at', '>=', now()->subMinutes(2))->latest('checked_in_at')->first();
        $repeated = $recentCheckIn && $recentCheckIn->subscription_id
            ? \App\Models\Subscription::whereKey($recentCheckIn->subscription_id)->whereIn('status',['active','completed'])->first() : null;
        $subscription = $repeated ?: $user->activeSubscription();

        if (!$subscription) {
            $latest = $user->latestSubscription();
            $expiredDate = ($latest && $latest->expires_at) 
                ? Carbon::parse($latest->expires_at)->format('Y-m-d')
                : null;

            $isDepleted = ($latest && !is_null($latest->total_sessions) && $latest->remaining_sessions <= 0);
            $isDateExpired = ($latest && $latest->expires_at && Carbon::parse($latest->expires_at)->endOfDay()->isPast());

            if ($isDepleted) {
                $statusCode = 'SESSIONS_DEPLETED';
                $msgAr = "لقد أكملت جميع الحصص المتاحة في اشتراكك ({$latest->total_sessions} حصة)، يرجى تجديد الاشتراك للمتابعة";
                $msgFr = "Vous avez terminé toutes les séances de votre abonnement ({$latest->total_sessions} séances). Veuillez renouveler votre abonnement.";
                $msgEn = "You have completed all sessions of your subscription ({$latest->total_sessions} sessions). Please renew.";
            } elseif ($isDateExpired) {
                $statusCode = 'SUBSCRIPTION_EXPIRED';
                $msgAr = "انتهت مدة اشتراكك منذ ($expiredDate)، يرجى تجديد الاشتراك للمتابعة";
                $msgFr = "La durée de votre abonnement a expiré le ($expiredDate). Veuillez renouveler votre abonnement.";
                $msgEn = "Your subscription duration has expired on ($expiredDate). Please renew.";
            } else {
                $statusCode = 'NO_ACTIVE_SUBSCRIPTION';
                $msgAr = 'لا يوجد اشتراك نشط لهذا العضو، يرجى مراجعة الاستقبال';
                $msgFr = 'Aucun abonnement actif trouvé pour ce membre. Veuillez voir la réception.';
                $msgEn = 'No active subscription found for this member.';
            }

            return response()->json([
                'success' => false,
                'access_granted' => false,
                'status_code' => $statusCode,
                'message' => $msgAr,
                'message_ar' => $msgAr,
                'message_fr' => $msgFr,
                'message_en' => $msgEn,
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
                    'expires_at' => $expiredDate,
                    'total_sessions' => $latest->total_sessions,
                    'remaining_sessions' => $latest->remaining_sessions,
                    'sessions_used' => $latest->sessions_used ?? 0,
                    'is_depleted' => $isDepleted,
                    'is_date_expired' => $isDateExpired
                ] : null
            ], 403);
        }

        $alreadyCheckedIn = false;
        if ($repeated) {
            $alreadyCheckedIn = true;
        } else {
            // Record new Attendance
            Attendance::create([
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'checked_in_at' => now(),
            ]);

            // Record activity log
            Activity::create([
                'entity_id' => $user->id,
                'type' => 'attendance',
                'description' => 'تسجيل دخول عبر بوابة RFID (Gate Entry)'
            ]);

            // Deduct 1 session if subscription tracks session counts
            if (!is_null($subscription->total_sessions) && $subscription->remaining_sessions > 0) {
                $subscription->decrement('remaining_sessions');
                $subscription->increment('sessions_used');
                $subscription->refresh();

                // If remaining sessions reach 0, mark as completed
                if ($subscription->remaining_sessions <= 0) {
                    $subscription->update(['status' => 'completed']);
                }
            }
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
            'message_ar' => $alreadyCheckedIn 
                ? 'تم تسجيل الدخول بالفعل منذ لحظات، تفضل بالدخول!'
                : 'تم التحقق بنجاح، تفضل بالدخول وتمرين موفق!',
            'message_fr' => $alreadyCheckedIn
                ? 'Passage déjà enregistré il y a quelques instants. Bienvenue !'
                : 'Accès autorisé avec succès ! Bonne séance !',
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
                'cycle_name' => $subscription->package?->cycle?->name,
                'expires_at' => $subscription->expires_at ? Carbon::parse($subscription->expires_at)->format('Y-m-d') : null,
                'days_left' => $daysLeft,
                'total_sessions' => $subscription->total_sessions,
                'remaining_sessions' => $subscription->remaining_sessions,
                'sessions_used' => $subscription->sessions_used ?? 0,
                'is_unlimited_sessions' => is_null($subscription->total_sessions),
                'is_expiring_soon' => ($daysLeft !== null && $daysLeft <= 5) || (!is_null($subscription->remaining_sessions) && $subscription->remaining_sessions <= 2)
            ],
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name
            ] : null,
            'checked_in_at' => now()->format('Y-m-d H:i:s')
        ]);
        }, 3);
    }

    private function denied(string $code, string $key)
    {
        return response()->json(['success'=>false,'access_granted'=>false,'status_code'=>$code,
            'message'=>__('membership.'.$key),
            'message_ar'=>__('membership.'.$key, [], 'ar'),
            'message_fr'=>__('membership.'.$key, [], 'fr'),
            'message_en'=>__('membership.'.$key, [], 'en')], 403);
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
