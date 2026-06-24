<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('name', 50)->comment('渠道名称');
            $table->enum('type', ['email', 'webhook', 'dingtalk', 'wecom', 'feishu'])->default('email')->comment('渠道类型');
            $table->json('config')->comment('渠道配置');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
        });
        DB::statement("ALTER TABLE `notification_channels` COMMENT = '通知渠道表'");
    }

    public function down()
    {
        Schema::dropIfExists('notification_channels');
    }
};
