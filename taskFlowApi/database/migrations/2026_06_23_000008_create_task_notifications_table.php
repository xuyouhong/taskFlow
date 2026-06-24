<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('task_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('task_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('任务hash_id');
            $table->string('channel_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('渠道hash_id');
            $table->enum('notify_on', ['success', 'failure', 'both'])->default('failure')->comment('通知触发条件');
            $table->tinyInteger('consecutive_failures_threshold')->unsigned()->default(1)->comment('连续失败次数阈值');
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_id');
            $table->index('channel_id');
        });
        DB::statement("ALTER TABLE `task_notifications` COMMENT = '任务通知渠道绑定表'");
    }

    public function down()
    {
        Schema::dropIfExists('task_notifications');
    }
};
