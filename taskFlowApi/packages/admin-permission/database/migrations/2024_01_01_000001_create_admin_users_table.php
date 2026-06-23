<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('email', 100)->unique()->nullable()->comment('邮箱');
            $table->string('password')->comment('密码');
            $table->string('real_name', 50)->nullable()->comment('真实姓名');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常,0禁用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['username', 'status']);
            $table->index('email');
            $table->index('status');
        });
        DB::statement("ALTER TABLE `admin_users` COMMENT = '管理员用户表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_users');
    }
};
