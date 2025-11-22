<?php

namespace Travelx24\SupportChat\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Tabour\SupportChat\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * SupportChatController (Business side)
 *
 * Handles the business-facing support chat with the Super Admin:
 *  - index(): show the thread for the authenticated business user,
 *             and mark admin→this-user messages as read
 *  - store(): send a new message from business user to admin
 *
 * NOTE: Logic is unchanged; only namespace + view namespace adjusted.
 */
class SupportChatController extends Controller
{
    /**
     * GET /business/support
     *
     * Show the support chat for the authenticated business user.
     */
    public function index(Request $r)
    {
        $bizId  = $r->user()->business_id; // current business
        $userId = $r->user()->id;          // current business-side user

        // Mark unread admin→this-user messages as read now (business-side read flag)
        SupportMessage::where('business_id', $bizId)
            ->where('sender_role', 'admin')
            ->where('context_user_id', $userId) // message targeted to me
            ->whereNull('read_by_business_at')
            ->update(['read_by_business_at' => now()]);

        // Fetch only my thread:
        // - my outgoing business messages
        // - admin replies that target me
        $messages = SupportMessage::where('business_id', $bizId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    // My own messages (I am the business sender)
                    $q2->where('sender_role', 'business')
                       ->where('sender_id', $userId);
                })->orWhere(function ($q3) use ($userId) {
                    // Admin replies that target me (context_user_id = my id)
                    $q3->where('sender_role', 'admin')
                       ->where('context_user_id', $userId);
                });
            })
            ->orderBy('id')
            ->get();

        // Get a Super Admin (first by id) for contact info card in the UI
        $admin = User::where('is_superadmin', 1)->orderBy('id')->first();

        // نستخدم namespace الواجهات الخاص بالباكج
        return view('support-chat::business.support', compact('messages', 'admin'));
    }

    /**
     * POST /business/support
     *
     * Send a new message from the authenticated business user to the admin.
     */
    public function store(Request $r)
    {
        $r->validate(['body' => 'required|string|min:1']);

        $bizId = $r->user()->business_id;

        $msg = SupportMessage::create([
            'business_id' => $bizId,
            'sender_role' => 'business',
            'sender_id'   => $r->user()->id,
            'body'        => trim($r->body),
        ]);

        // AJAX response (used by the chat to append a bubble without reload)
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

        // Non-AJAX fallback
        return back();
    }
}
