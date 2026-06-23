<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('name', 50)->comment('角色名称');
            $table->string('slug', 50)->unique()->comment('角色标识');
            $table->text('description')->nullable()->comment('角色描述');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常,0禁用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'status']);
            $table->index('status');
        });
        DB::statement("ALTER TABLE `admin_roles` COMMENT = '角色表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_roles');
    }
};
