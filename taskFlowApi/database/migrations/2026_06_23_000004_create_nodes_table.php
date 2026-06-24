<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('name', 50)->comment('节点名称');
            $table->string('ip', 45)->comment('节点IP');
            $table->smallInteger('agent_port')->unsigned()->default(9501)->comment('Agent端口');
            $table->string('hostname', 100)->nullable()->comment('主机名');
            $table->string('agent_token', 64)->comment('Agent认证Token');
            $table->string('allowed_command_prefix')->nullable()->comment('允许执行的命令前缀');
            $table->enum('status', ['online', 'offline'])->default('offline')->comment('状态');
            $table->timestamp('last_heartbeat_at')->nullable()->comment('最后心跳时间');
            $table->smallInteger('cpu_cores')->nullable()->comment('CPU核心数');
            $table->integer('memory_total_mb')->nullable()->comment('总内存(MB)');
            $table->string('agent_version', 20)->nullable()->comment('Agent版本');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('ip');
        });
        DB::statement("ALTER TABLE `nodes` COMMENT = 'Shell执行节点表'");
    }

    public function down()
    {
        Schema::dropIfExists('nodes');
    }
};
