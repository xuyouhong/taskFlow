<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('name', 50)->comment('权限名称');
            $table->string('slug', 100)->unique()->comment('权限标识');
            $table->string('http_method')->nullable()->comment('HTTP方法');
            $table->text('http_path')->nullable()->comment('HTTP路径');
            $table->text('description')->nullable()->comment('权限描述');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常,0禁用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'status']);
            $table->index('status');
        });
        DB::statement("ALTER TABLE `admin_permissions` COMMENT = '权限表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_permissions');
    }
};
