<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('project_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('项目hash_id');
            $table->string('name', 100)->comment('任务名称');
            $table->text('description')->nullable()->comment('任务描述');
            $table->string('cron_expression', 50)->comment('cron表达式');
            $table->string('timezone', 50)->default('Asia/Shanghai')->comment('时区');
            $table->enum('executor_type', ['http', 'shell', 'job', 'mq'])->comment('执行器类型');
            $table->json('executor_config')->comment('执行器配置');
            $table->tinyInteger('retry_times')->unsigned()->default(0)->comment('重试次数');
            $table->integer('retry_interval')->unsigned()->default(60)->comment('重试间隔(秒)');
            $table->integer('timeout')->unsigned()->default(300)->comment('超时时间(秒)');
            $table->enum('concurrency_strategy', ['allow', 'forbid', 'replace'])->default('forbid')->comment('并发策略');
            $table->enum('misfire_strategy', ['skip', 'fire_once', 'fire_all'])->default('skip')->comment('失火策略');
            $table->tinyInteger('priority')->default(0)->comment('优先级');
            $table->enum('status', ['enabled', 'disabled', 'paused'])->default('enabled')->comment('状态');
            $table->timestamp('last_run_at')->nullable()->comment('上次执行时间');
            $table->timestamp('next_run_at')->nullable()->comment('下次执行时间');
            $table->enum('last_run_status', ['success', 'failed', 'timeout', 'running'])->nullable()->comment('上次执行状态');
            $table->string('created_by', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('创建人hash_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['next_run_at', 'status']);
            $table->index('project_id');
            $table->index('status');
            $table->index('executor_type');
        });
        DB::statement("ALTER TABLE `tasks` COMMENT = '定时任务表'");
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
