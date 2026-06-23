<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('title')->comment('标题');
            $table->text('content')->comment('内容');
            $table->tinyInteger('type')->default(1)->comment('类型：1-通知，2-通告');
            $table->tinyInteger('priority')->default(1)->comment('优先级：1-普通，2-重要，3-紧急');
            $table->string('sender_id', 20)->comment('发送者ID');
            $table->tinyInteger('target_type')->default(1)->comment('接收对象类型：1-所有用户，2-指定角色，3-指定用户');
            $table->json('target_values')->nullable()->comment('接收对象值，如角色hash_id或用户hash_id数组');
            $table->timestamp('publish_time')->nullable()->comment('发布时间');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            $table->tinyInteger('status')->default(1)->comment('状态：1-草稿，2-已发布，3-已撤销');
            $table->timestamps();

            // 索引
            $table->index('type', 'admin_notifications_type_index');
            $table->index('status', 'admin_notifications_status_index');
            $table->index('sender_id', 'admin_notifications_sender_id_index');
            $table->index('publish_time', 'admin_notifications_publish_time_index');
            $table->index('expire_time', 'admin_notifications_expire_time_index');
        });
        DB::statement("ALTER TABLE `admin_notifications` COMMENT = '通知主表'");

        Schema::create('admin_user_notifications', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('user_id', 20)->comment('用户ID');
            $table->string('notification_id', 20)->comment('通知ID');
            $table->tinyInteger('is_read')->default(0)->comment('是否已读：0-未读，1-已读');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->timestamps();

            // 唯一索引
            $table->unique(['user_id', 'notification_id'], 'admin_user_notifications_user_id_notification_id_unique');

            // 普通索引
            $table->index('user_id', 'admin_user_notifications_user_id_index');
            $table->index('notification_id', 'admin_user_notifications_notification_id_index');
            $table->index('is_read', 'admin_user_notifications_is_read_index');
            $table->index('read_at', 'admin_user_notifications_read_at_index');

            // 外键约束
            $table->foreign('user_id')->references('hash_id')->on('admin_users')->onDelete('cascade');
            $table->foreign('notification_id')->references('hash_id')->on('admin_notifications')->onDelete('cascade');
        });
        DB::statement("ALTER TABLE `admin_user_notifications` COMMENT = '用户通知关联表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_user_notifications');
        Schema::dropIfExists('admin_notifications');
    }
};
