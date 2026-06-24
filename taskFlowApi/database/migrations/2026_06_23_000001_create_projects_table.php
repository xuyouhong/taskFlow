<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('name', 50)->comment('项目名称');
            $table->string('code', 50)->unique()->comment('项目编码');
            $table->text('description')->nullable()->comment('项目描述');
            $table->string('owner_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('负责人hash_id');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常,0禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('status');
            $table->index('code');
        });
        DB::statement("ALTER TABLE `projects` COMMENT = '项目表'");
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
