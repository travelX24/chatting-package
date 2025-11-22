<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business-side chat settings
    |--------------------------------------------------------------------------
    |
    | إعدادات مسارات محادثة الدعم في لوحة الأعمال.
    | يمكن للمشروع الذي يستخدم الباكدج أن يغير هذه القيم عبر:
    |   config('support_chat.business.*')
    |
    */

    'business' => [
        // Middlewares المستخدمة في /business/support
        'middleware' => [
            'web',
            'auth',
            'verified',
            // هذا middleware موجود في مشروعك الأساسي
            \App\Http\Middleware\EnsureBusinessUser::class,
        ],

        // Prefix لمسارات البزنس (نتركه فارغ لأن المسار عندك أصلاً يبدأ بـ /business/...)
        'prefix' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin inbox settings
    |--------------------------------------------------------------------------
    |
    | إعدادات مسارات إنبوكس السوبر أدمن (Conversations).
    |
    */

    'admin' => [
        'middleware' => [
            'web',
            'auth',
            // Middleware السوبر أدمن من مشروعك
            \App\Http\Middleware\SuperAdminMiddleware::class,
        ],

        // Prefix لمسارات السوبرأدمن: /superadmin/...
        'prefix' => 'superadmin',
    ],

];
