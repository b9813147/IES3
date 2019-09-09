<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_sub_event extends Model
{
    protected $table = 'fc_targetc_sub_event';
    protected $primaryKey = 'id';
    protected $fillable = [
        'rtype',
        'MemberID',      //resource type，1 => cms，2` => 個人教材
        'fc_event_id',  //發佈者id
        'token',        //學習任務id
        'name',         //權重排序用
        'movie_path',   //子任務名稱
        'movie_script', //影片 連結或上傳
        'file_name',    //影片描述
        'knowledge',    //檔案名稱
    ];
}
