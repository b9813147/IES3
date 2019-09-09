<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictsAllSchool extends Model
{
    protected $table = 'districts_all_schools';
    protected $primaryKey = 'id';
    protected $fillable = [
        'schoolName',                   //學校名稱
        'schoolId',                     //學校ID
        'teachnum',                     //老師帳號啟用數量
        'studentnum',                   //學生帳號啟用數量
        'patriarchnum',                 //家長帳號啟用數量
        'teachlogintable',              //老師登入數
        'studylogintable',              // 學生登入數
        'curriculum',                   //課程總數
        'electronicalnote',              //電子筆記
        'uploadMovie',                  //上傳影片
        'production',                   //作業作品數
        'overturnClass',                //翻轉課堂
        'analogyTest',                  //模擬測驗
        'onlineTest',                   //線上測驗
        'interClassCompetition',        //班級競賽
        'HiTeach',                      //HiTeach
        'performanceLogin',             //成績登入
        'mergeActivity',                //合並活動
        'onlineChecking',               //網路閱卷
        'allLearningProcess',           //學習歷程總數
        'chartdata',                  //智慧教堂應用下２個圖表
        'personAge',                    //個人所属
        'areaShare',                    //區级分享
        'schoolShare',                  //校级分享
        'overallResourece',             //總資源分享數
        'subjectnum',                 //題目數學校ID
        'examinationnum',             //試卷數ID
        'textbooknum',              //教材數得時間
        'underway',                 //進行中時間
        'unfinished',                 //進行中時間
        'achieve',                  //已完成時間
        'totalresources',             //自學任務總數時間
        'onlineTestComplete',           //線上測驗完成率
        'productionPercentage',         //作業作品完成率
        'semester',                  //學期 0上１下
        'year',                        //學年
    ];
}

