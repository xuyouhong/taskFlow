<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('task_log_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('任务日志hash_id');
            $table->string('channel_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('渠道hash_id');
            $table->enum('status', ['sent', 'failed', 'skipped'])->comment('发送状态');
            $table->text('content')->nullable()->comment('通知内容');
            $table->string('error_message', 500)->nullable()->comment('错误信息');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_log_id');
            $table->index('channel_id');
            $table->index('status');
        });
        DB::statement("ALTER TABLE `notification_logs` COMMENT = '通知发送记录表'");
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};
