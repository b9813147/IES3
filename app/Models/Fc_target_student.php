<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_target_student extends Model
{
    protected $table = 'fc_target_student';
    protected $primaryKey = 'id';
    protected $fillable = [
        'MemberID',
        'fc_target_id',
        'content',
        'finish_flag',
        'updated_dt',
    ];
}
