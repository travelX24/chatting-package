<?php

namespace Travelx24\ChattingPackage;

use Illuminate\Support\ServiceProvider;

class SupportChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // دمج إعدادات الباكدج داخل المشروع
        $this->mergeConfigFrom(
            __DIR__ . '/../config/support_chat.php',
            'support_chat'
        );
    }

    public function boot(): void
    {
        // تحميل الراوت من الباكج
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // تحميل الواجهات support-chat::
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'support-chat');

        // تحميل المايجريشن
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // نشر الملفات (اختياري)
        $this->publishes([
            __DIR__ . '/../config/support_chat.php' => config_path('support_chat.php'),
        ], 'support-chat-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/support-chat'),
        ], 'support-chat-views');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'support-chat-migrations');
    }
}
