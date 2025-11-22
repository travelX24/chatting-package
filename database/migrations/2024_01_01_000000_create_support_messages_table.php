<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('business_id');

            $t->enum('sender_role', ['business', 'admin']);

            $t->unsignedBigInteger('sender_id');

            // مهم: هذا العمود موجود في الموديل والمنطق لكن ما كان في المايجريشن القديم
            // نضيفه هنا عشان يشتغل الباكدج صح في أي مشروع جديد
            $t->unsignedBigInteger('context_user_id')->nullable();

            $t->text('body');

            $t->timestamp('read_by_admin_at')->nullable();
            $t->timestamp('read_by_business_at')->nullable();

            $t->timestamps();

            $t->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
