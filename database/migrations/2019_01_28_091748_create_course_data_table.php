<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCourseDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_data' , function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_no')->comment('課程ID')->index();
            $table->integer('targetYear')->nullable()->comment('目標年');
            $table->integer('targetMonth')->nullable()->comment('目標月');
            $table->integer('targetDay')->nullable()->comment('目標日');
            $table->integer('sokratesCount')->default('0')->nullable()->comment('蘇格拉迪數量');
            $table->integer('enoteCount')->default('0')->nullable()->comment('電子筆記數量');
            $table->integer('cmsCount')->default('0')->nullable()->comment('課堂影片數量');
            $table->integer('hiTeachCount')->default('0')->nullable()->comment('hiTeach數量');
            $table->integer('omrCount')->default('0')->nullable()->comment('閱卷數量');
            $table->integer('eventCount')->default('0')->nullable()->comment('班際智慧服務');
            $table->integer('loginScoreCount')->default('0')->nullable()->comment('成績登錄數量');
            $table->integer('combineCount')->default('0')->nullable()->comment('合併活動');
            $table->integer('assignmentCount')->default('0')->nullable()->comment('線上測驗數量');
            $table->integer('gradeName')->default('0')->nullable()->comment('年級');
            $table->integer('testingStuNum')->default('0')->nullable()->comment('線上測驗學生總數');
            $table->integer('testingStuCount')->default('0')->nullable()->comment('線上測驗學生完成數');
            $table->integer('homeworkCount')->default('0')->nullable()->comment('線上作業');
            $table->integer('homeworkStuNum')->default('0')->nullable()->comment('線上作業總數');
            $table->integer('homeworkStuCount')->default('0')->nullable()->comment('線上作業完成數');
            $table->integer('fcEventCount')->default('0')->nullable()->comment('課綱數量');
            $table->integer('fcEventStuNum')->default('0')->nullable()->comment('學生總數');
            $table->integer('fcEventStuCount')->default('0')->nullable()->comment('學生完成數');
            $table->integer('fcEventStuInProgress')->default('0')->nullable()->comment('學生進行中');

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
        Schema::dropIfExists('course_data');
    }
}
