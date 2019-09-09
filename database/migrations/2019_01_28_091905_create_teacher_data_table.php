<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeacherDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teacher_data' , function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->comment('會員ID')->index();
            $table->integer('targetYear')->nullable()->comment('目標年份');
            $table->integer('targetMonth')->nullable()->comment('目標月份');
            $table->integer('targetDay')->nullable()->comment('日');
            $table->integer('resourceCount')->default('0')->nullable()->comment('資源數量');
            $table->integer('resourceSchSharedCount')->default('0')->nullable()->comment('資源學校分享數量');
            $table->integer('resourceDisSharedCount')->default('0')->nullable()->comment('非資源分享數量');
            $table->integer('testPaperCount')->default('0')->nullable()->comment('試卷數量');
            $table->integer('testPaperSchSharedCount')->default('0')->nullable()->comment('學校試卷分享數量');
            $table->integer('testPaperDisSharedCount')->default('0')->nullable()->comment('學區試卷分享數量');
            $table->integer('testItemCount')->default('0')->nullable()->comment('試題數量');
            $table->integer('testItemSchSharedCount')->default('0')->nullable()->comment('學校試題分享數量');
            $table->integer('testItemDisSharedCount')->default('0')->nullable()->comment('學區試題分享數量');
            $table->integer('sokratesCount')->default('0')->nullable()->comment('蘇格拉迪數量');
            $table->integer('enoteCount')->default('0')->nullable()->comment('電子筆記數量');
            $table->integer('cmsCount')->default('0')->nullable()->comment('課堂影片數量');
            $table->integer('hiTeachCount')->default('0')->nullable()->comment('hiTeach數量');
            $table->integer('omrCount')->default('0')->nullable()->comment('閱卷數量');
            $table->integer('eventCount')->default('0')->nullable()->comment('班際智慧服務');
            $table->integer('loginScoreCount')->default('0')->nullable()->comment('成績登錄數量');
            $table->integer('combineCount')->default('0')->nullable()->comment('合併活動');
            $table->integer('assignmentCount')->default('0')->nullable()->comment('線上測驗數量');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teacher_data');
    }
}
