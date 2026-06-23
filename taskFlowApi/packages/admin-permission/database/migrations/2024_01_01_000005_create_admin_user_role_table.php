<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_user_role', function (Blueprint $table) {
            $table->string('user_id', 20);
            $table->string('role_id', 20);

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('hash_id')->on('admin_users')->onDelete('cascade');
            $table->foreign('role_id')->references('hash_id')->on('admin_roles')->onDelete('cascade');

            $table->index(['user_id', 'role_id']);
        });
        DB::statement("ALTER TABLE `admin_user_role` COMMENT = '用户角色关联表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_user_role');
    }
};
