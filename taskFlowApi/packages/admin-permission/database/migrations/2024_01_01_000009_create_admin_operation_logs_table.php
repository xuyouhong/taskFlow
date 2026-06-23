<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_operation_logs', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('user_id', 20)->nullable()->comment('用户ID');
            $table->string('username', 50)->comment('用户名');
            $table->string('method', 10)->comment('请求方法');
            $table->string('path')->comment('请求路径');
            $table->text('params')->nullable()->comment('请求参数');
            $table->text('response')->nullable()->comment('响应数据');
            $table->string('ip', 45)->comment('IP地址');
            $table->string('user_agent')->comment('用户代理');
            $table->integer('status_code')->comment('状态码');
            $table->integer('duration')->comment('耗时(毫秒)');
            $table->timestamp('operated_at')->comment('操作时间');

            $table->foreign('user_id')->references('hash_id')->on('admin_users')->onDelete('set null');

            $table->index('user_id');
            $table->index('username');
            $table->index('method');
            $table->index('path');
            $table->index('operated_at');
            $table->index(['user_id', 'operated_at']);
        });
        DB::statement("ALTER TABLE `admin_operation_logs` COMMENT = '操作日志表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_operation_logs');
    }
};
