<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_login_logs', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('user_id', 20)->nullable()->comment('用户ID');
            $table->string('username', 50)->comment('用户名');
            $table->string('ip', 45)->comment('登录IP');
            $table->string('user_agent')->comment('用户代理');
            $table->string('browser')->nullable()->comment('浏览器');
            $table->string('os')->nullable()->comment('操作系统');
            $table->string('device')->nullable()->comment('设备类型');
            $table->string('country')->nullable()->comment('国家');
            $table->string('region')->nullable()->comment('地区');
            $table->string('city')->nullable()->comment('城市');
            $table->timestamp('login_at')->comment('登录时间');
            $table->timestamp('logout_at')->nullable()->comment('退出时间');
            $table->integer('online_duration')->default(0)->comment('在线时长(秒)');
            $table->tinyInteger('status')->default(1)->comment('登录状态:1成功,0失败');
            $table->text('message')->nullable()->comment('备注信息');

            $table->foreign('user_id')->references('hash_id')->on('admin_users')->onDelete('set null');

            $table->index('user_id');
            $table->index('username');
            $table->index('login_at');
            $table->index(['user_id', 'login_at']);
        });
        DB::statement("ALTER TABLE `admin_login_logs` COMMENT = '登录日志表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_login_logs');
    }
};
