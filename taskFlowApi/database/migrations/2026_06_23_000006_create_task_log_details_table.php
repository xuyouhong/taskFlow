<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('task_log_details', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('task_log_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->comment('任务日志hash_id');
            $table->longText('stdout_content')->nullable()->comment('标准输出');
            $table->longText('stderr_content')->nullable()->comment('标准错误');
            $table->timestamps();
            $table->softDeletes();

            $table->index('task_log_id');
        });
        DB::statement("ALTER TABLE `task_log_details` COMMENT = '执行日志详情表'");
    }

    public function down()
    {
        Schema::dropIfExists('task_log_details');
    }
};
