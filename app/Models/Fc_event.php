<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_event extends Model
{
    public $timestamps = false;
    protected $table = 'fc_event';
    protected $primaryKey = 'id';
    protected $fillable = [
        'MemberID',
        'CourseNO',
        'name',          //名稱
        'script',        //描述
        'knowledge',     //知識點
        'token',         //權重排序用
        'create_time',   //建立時間
        'finish_time',   //完成時間
        'public_flag',   //發佈狀態 1-發佈
        'active_flag',   //啟用狀態 1-啟用
        'messages_flag', //討論區啟用狀態 1-啟用
        'SourceID',      //複製來源的 fc_event id
        'fc_outline_id', //fc_outline id
        'status',        //刪除註記 D-logical delete
        'css_left',
        'css_top',
    ];
}
