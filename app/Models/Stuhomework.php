<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stuhomework extends Model
{
    public $timestamps = false;
    protected $table = 'stuhomework';
    protected $primaryKey = 'HomeworkNO';
    protected $fillable = [
        'MemberID',
        'HomeMode',
        'TeaDescription',
        'Txt',
        'SubmitTime',
        'ViewCounter',
        'Status',
    ];
}
