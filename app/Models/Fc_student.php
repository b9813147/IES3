<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_student extends Model
{
    protected $table='fc_student';
    protected $primaryKey = 'id';
    protected $fillable = [
        'fc_sub_event_id',
        'MemberID',
        'read_time',           //開始閱讀時間
        'read_finish_time',    //完成閱讀時間
        'read_flag',           //閱讀旗標 1-完成
    ];
}
