<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('_seq')->autoIncrement();
            $table->string('hash_id', 20)->charset('utf8mb4')->collation('utf8mb4_bin')->unique();
            $table->string('key', 100)->unique()->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('description', 255)->nullable()->comment('配置描述');
            $table->timestamps();
            $table->softDeletes();

            $table->index('key');
        });
        DB::statement("ALTER TABLE `system_settings` COMMENT = '系统配置表'");
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
