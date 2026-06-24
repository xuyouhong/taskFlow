<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('task_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('task_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('任务hash_id');
            $table->char('trigger_id', 36)->comment('触发ID(同一次触发共享)');
            $table->char('execution_id', 36)->unique()->comment('执行ID(每次执行唯一)');
            $table->enum('trigger_type', ['schedule', 'manual', 'retry'])->comment('触发类型');
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'timeout', 'cancelled'])->comment('状态');
            $table->string('node_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable()->comment('执行节点hash_id');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->integer('duration_ms')->unsigned()->nullable()->comment('执行时长(毫秒)');
            $table->json('request_snapshot')->nullable()->comment('请求快照');
            $table->string('response_summary', 500)->nullable()->comment('响应摘要');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->tinyInteger('retry_count')->unsigned()->default(0)->comment('重试次数');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'created_at']);
            $table->index('trigger_id');
            $table->index('execution_id');
            $table->index('status');
        });
        DB::statement("ALTER TABLE `task_logs` COMMENT = '执行日志主表'");
    }

    public function down()
    {
        Schema::dropIfExists('task_logs');
    }
};
