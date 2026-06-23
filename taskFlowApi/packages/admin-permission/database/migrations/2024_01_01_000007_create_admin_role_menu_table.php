<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('admin_role_menu', function (Blueprint $table) {
            $table->string('role_id', 20);
            $table->string('menu_id', 20);

            $table->primary(['role_id', 'menu_id']);
            $table->foreign('role_id')->references('hash_id')->on('admin_roles')->onDelete('cascade');
            $table->foreign('menu_id')->references('hash_id')->on('admin_menus')->onDelete('cascade');

            $table->index(['role_id', 'menu_id']);
        });
        DB::statement("ALTER TABLE `admin_role_menu` COMMENT = '角色菜单关联表'");
    }

    public function down()
    {
        Schema::dropIfExists('admin_role_menu');
    }
};
