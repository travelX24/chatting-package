<?php

use Illuminate\Support\Facades\Route;
use Travelx24\ChattingPackage\Http\Controllers\Business\SupportChatController;
use Travelx24\ChattingPackage\Http\Controllers\Admin\SupportInboxController;
\use App\Http\Middleware\SuperAdminMiddleware;

/*
|--------------------------------------------------------------------------
| Support Chat Package Routes
|--------------------------------------------------------------------------
| يربط:
| - واجهة البزنس /business/support
| - واجهة السوبرأدمن /superadmin/conversations ...
*/

/**
 * Business → Support (لوحة الأعمال)
 *  GET  /business/support        → عرض المحادثة
 *  POST /business/support        → إرسال رسالة جديدة
 */
Route::middleware(['auth', 'verified', 'sp'])->group(function () {
        Route::get('/business/support', [SupportChatController::class, 'index'])
        ->name('business.support');

    Route::post('/business/support', [SupportChatController::class, 'store'])
        ->name('business.support.store');
});

/**
 * SuperAdmin → Conversations (Inbox)
 *
 *  GET  /superadmin/conversations                                → index
 *  GET  /superadmin/conversations/{business}                     → users
 *  POST /superadmin/conversations/{business}/user/{user}/reply   → replyToUser
 *  POST /superadmin/conversations/{business}/user/{user}/ack     → ackUser
 *  GET  /superadmin/conversations/counters                       → counters
 *  GET  /superadmin/conversations/counters-map                   → countersMap
 *  GET  /superadmin/conversations/{business}/user/{user}/stream  → stream
 */
// Route::middleware(['auth', 'sa'])
//     ->prefix('admin')
//     ->name('admin.')
//     ->group(function () {
        Route::get('/conversations', [SupportInboxController::class, 'index'])
            ->name('conversations');

        Route::get('/conversations/{business}', [SupportInboxController::class, 'users'])
            ->name('conversations.show');

        Route::post(
            '/conversations/{business}/user/{user}/reply',
            [SupportInboxController::class, 'replyToUser']
        )->name('conversations.reply_user');

        Route::post(
            '/conversations/{business}/user/{user}/ack',
            [SupportInboxController::class, 'ackUser']
        )->name('conversations.ack_user');

        Route::get(
            '/conversations/counters',
            [SupportInboxController::class, 'counters']
        )->name('conversations.counters');

        Route::get(
            '/conversations/counters-map',
            [SupportInboxController::class, 'countersMap']
        )->name('conversations.counters_map');

        Route::get(
            '/conversations/{business}/user/{user}/stream',
            [SupportInboxController::class, 'stream']
        )->name('conversations.stream');
    // });
