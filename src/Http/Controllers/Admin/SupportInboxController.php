<?php

namespace Travelx24\ChattingPackage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Travelx24\ChattingPackage\Models\SupportMessage;
use App\Models\User;
use App\Models\ServiceProviderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SupportInboxController (Super Admin side)
 *
 * Admin inbox for Business ↔ Admin support conversations.
 */
class SupportInboxController extends Controller
{
    /**
     * GET /superadmin/conversations
     */
    public function index(Request $r)
    {
        $q = trim((string) $r->input('q')); // optional search query

        // Aggregate at DB level: last message id and unread count (business→admin)
        $rows = SupportMessage::select(
                'business_id',
                DB::raw('MAX(id) as last_id'),
                DB::raw('SUM(CASE WHEN sender_role="business" AND read_by_admin_at IS NULL THEN 1 ELSE 0 END) as unread')
            )
            ->groupBy('business_id')
            ->orderByDesc('last_id')
            ->get();

        // Hydrate simple DTOs for the blade view
$items = $rows->map(function ($r) {
    // هنا business_id = user_id كما قلت
    $user = User::find($r->business_id);

    // بروفايل الوكالة المرتبط بهذا اليوزر
    $profile = $user
        ? ServiceProviderProfile::where('user_id', $user->id)->first()
        : null;

    return (object) [
        'business' => $profile,                         // بيانات الوكالة (company_name…)
        'user'     => $user,                            // اليوزر نفسه (name, email…)
        'last'     => SupportMessage::find($r->last_id),
        'unread'   => (int) $r->unread,
    ];
});


        // If a query is provided, filter by business name/slug (case-insensitive)
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $items = $items->filter(function ($it) use ($qLower) {
                return str_contains(mb_strtolower($it->business->name ?? ''), $qLower)
                    || str_contains(mb_strtolower($it->business->slug ?? ''), $qLower);
            })->values();
        }

        // ملاحظة: نستخدم namespace للـ views خاص بالباكج
        return view('support-chat::SuperAdmin.support.index', compact('items'));
    }

    /**
     * GET /superadmin/conversations/{business}
     */
/**
 * GET /superadmin/conversations/{business}
 */
public function users(int $businessId)
{
    // في مشروعك: business_id = رقم الـ User
    $businessUser = User::findOrFail($businessId);

    // لو حاب تربط ببروفايل الوكالة
    $business = \App\Models\ServiceProviderProfile::where('user_id', $businessUser->id)->first();

    // Group by sender_id for business messages within this business
    $userRows = SupportMessage::where('business_id', $businessUser->id)

            ->where('sender_role', 'business')
            ->select(
                'sender_id',
                DB::raw('MAX(id) AS last_id'),
                DB::raw('SUM(CASE WHEN read_by_admin_at IS NULL THEN 1 ELSE 0 END) AS unread')
            )
            ->groupBy('sender_id')
            ->orderByDesc('last_id')
            ->get();

$items = $userRows->map(function ($r) use ($businessUser) {
    $user = User::find($r->sender_id);

    $thread = SupportMessage::where('business_id', $businessUser->id)

                ->where(function ($q) use ($r) {
                    // (A) From business side: specific sender_id
                    $q->where(function ($q2) use ($r) {
                        $q2->where('sender_role', 'business')
                           ->where('sender_id', $r->sender_id);
                    })
                    // (B) From admin side: replies with context_user_id = the same user
                    ->orWhere(function ($q3) use ($r) {
                        $q3->where('sender_role', 'admin')
                           ->where('context_user_id', $r->sender_id);
                    });
                })
                ->orderBy('id')
                ->get();

            return (object) [
                'user'     => $user,
                'last'     => SupportMessage::find($r->last_id),
                'unread'   => (int) $r->unread,
                'messages' => $thread,
            ];
        });

return view('support-chat::SuperAdmin.support.users', [
    'business'   => $business,
    'businessId' => $businessId,   // رقم اليوزر صاحب المحادثة
    'items'      => $items,
]);
    }

    /**
     * POST /superadmin/conversations/{business}/user/{user}/ack
     */
public function ackUser(int $businessId, User $user)
{
    // Mark unread messages from this user as read now
    SupportMessage::where('business_id', $businessId)
        ->where('sender_role', 'business')
        ->where('sender_id', $user->id)
        ->whereNull('read_by_admin_at')
        ->update(['read_by_admin_at' => now()]);


        // Compute global unread (for sidebar counters, etc.)
        $total = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        return response()->json(['ok' => true, 'total_unread' => $total]);
    }

    /**
     * POST /superadmin/conversations/{business}/user/{user}/reply
     */
public function replyToUser(Request $r, int $businessId, User $user)
{
    $r->validate(['body' => 'required|string|min:1']);

    $msg = SupportMessage::create([
        'business_id'     => $businessId,
        'sender_role'     => 'admin',
        'sender_id'       => $r->user()->id,
        'context_user_id' => $user->id,
        'body'            => trim($r->body),
    ]);


        // AJAX response (no full reload)
        if ($r->expectsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => [
                    'id'          => $msg->id,
                    'sender_role' => $msg->sender_role,
                    'body'        => $msg->body,
                    'at'          => $msg->created_at->format('Y-m-d H:i'),
                ],
            ]);
        }

        // Non-AJAX fallback: redirect back (keeps legacy behavior)
        return back();
    }

    /**
     * GET /superadmin/conversations/counters
     */
    public function counters()
    {
        $total = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        return response()->json(['total_unread' => $total]);
    }

    /**
     * GET /superadmin/conversations/counters-map
     */
    public function countersMap()
    {
        // Query unread grouped by business
        $rows = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->select('business_id', DB::raw('COUNT(*) as unread'))
            ->groupBy('business_id')
            ->get();

        // Build map: business_id → unread count
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->business_id] = (int) $r->unread;
        }

        $total = array_sum($map);

        return response()->json([
            'total_unread' => $total,
            'businesses'   => $map,
        ]);
    }

    /**
     * GET /superadmin/conversations/preview-map
     *
     * (غير مربوط حالياً بأي route في الباكج، لكن أبقيناه حتى لا تفقده)
     */
    public function previewMap()
    {
        // Get last message id per business
        $lastRows = SupportMessage::select('business_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('business_id')
            ->get();

        // Load those last messages in bulk
        $ids  = $lastRows->pluck('last_id')->filter()->all();
        $msgs = SupportMessage::whereIn('id', $ids)->get(['id', 'business_id', 'body']);

        // Build preview map: business_id → body
        $map = [];
        foreach ($msgs as $m) {
            $map[(int) $m->business_id] = (string) ($m->body ?? '');
        }

        return response()->json(['previews' => $map]);
    }

    /**
     * GET /superadmin/conversations/{business}/user/{user}/stream?after=<id>
     */
public function stream(Request $r, int $businessId, User $user)
{
    $after = (int) $r->query('after', 0);

    // Fetch messages in this business/user context; optionally filter by id > after
    $messages = SupportMessage::where('business_id', $businessId)
        ->where(function ($q) use ($user) {
                $q->where(fn ($q2) => $q2->where('sender_role', 'business')->where('sender_id', $user->id))
                  ->orWhere(fn ($q3) => $q3->where('sender_role', 'admin')->where('context_user_id', $user->id));
            })
    
        ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
        ->orderBy('id')
        ->get(['id', 'sender_role', 'body', 'created_at']);

    // Unread counters
    $userUnread = SupportMessage::where('business_id', $businessId)
        ->where('sender_role', 'business')
        ->where('sender_id', $user->id)
        ->whereNull('read_by_admin_at')
        ->count();
        $totalUnread = SupportMessage::where('sender_role', 'business')
            ->where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        // JSON payload for polling UI
        return response()->json([
            'items' => $messages->map(fn ($m) => [
                'id'          => $m->id,
                'sender_role' => $m->sender_role,
                'body'        => $m->body,
                'at'          => $m->created_at->format('Y-m-d H:i'),
            ]),
            'max_id'       => $messages->max('id') ?? $after,
            'user_unread'  => $userUnread,
            'total_unread' => $totalUnread,
        ]);
    }
}
