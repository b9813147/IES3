<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSchoolDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_data' , function (Blueprint $table) {
            $table->increments('id');
            $table->integer('school_id')->nullable()->comment('學校ID')->index();
            $table->integer('targetYear')->nullable()->comment('目標年份');
            $table->integer('targetMonth')->nullable()->comment('目標月份');
            $table->integer('targetDay')->nullable()->comment('日');
            $table->integer('teacherCount')->default('0')->nullable()->comment('老師數量');
            $table->integer('studentCount')->default('0')->nullable()->comment('學生數量');
            $table->integer('courseCount')->default('0')->nullable()->comment('課程數量');
            $table->integer('teacherLoginTimes')->default('0')->nullable()->comment('老師登入數量');
            $table->integer('studentLoginTimes')->default('0')->nullable()->comment('學生登入數量');
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
        Schema::dropIfExists('school_data');
    }
}
