<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->string('hash_id', 20)->primary();
            $table->unsignedBigInteger('_seq')->unique();
            $table->string('parent_id', 20)->nullable()->comment('父级ID');
            $table->string('name', 50)->comment('菜单名称');
            $table->string('icon', 50)->nullable()->comment('菜单图标');
            $table->string('path')->nullable()->comment('菜单路径');
            $table->string('component')->nullable()->comment('组件路径');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('type')->default(1)->comment('类型:1菜单,2按钮');
            $table->tinyInteger('status')->default(1)->comment('状态:1显示,0隐藏');
            $table->tinyInteger('is_link')->default(0)->comment('是否外链:1是,0否');
            $table->tinyInteger('keep_alive')->default(1)->comment('是否缓存:1是,0否');
            $table->text('description')->nullable()->comment('菜单描述');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'status']);
            $table->index('sort');
            $table->index('status');
        });
        DB::statement("ALTER TABLE `admin_menus` COMMENT = '菜单表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_menus');
    }
};
