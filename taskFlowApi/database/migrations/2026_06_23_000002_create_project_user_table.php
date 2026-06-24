<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('project_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('项目hash_id');
            $table->string('user_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('用户hash_id');
            $table->enum('role', ['owner', 'member', 'viewer'])->default('member')->comment('项目角色');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'user_id']);
            $table->index('project_id');
            $table->index('user_id');
        });
        DB::statement("ALTER TABLE `project_user` COMMENT = '项目成员表'");
    }

    public function down()
    {
        Schema::dropIfExists('project_user');
    }
};
