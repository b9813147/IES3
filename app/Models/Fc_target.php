<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_target extends Model
{
    protected $table = 'fc_target';
    protected $primaryKey = 'id';
    protected $fillable = [
        'fc_event_id',      //學習任務id
        'target_type',      //目標類型 1-檔案上傳、2-線上評量、3-線上回應
        'name',             //目標名稱
        'script',           //目標描述
        'TPID',             //試卷id
        'active_flag',      //啟用狀態 1-啟用
    ];
}
