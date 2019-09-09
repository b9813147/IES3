<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exscore extends Model
{
    // public $timestamps = false;
    protected $table = 'exscore';
    protected $primaryKey = 'ExNO';
    protected $fillable = [
        'MemberID',
        'SubmitTime',
        'Score',
        'Judgment',
        'AnsNum',
        'TrueNum',
        'TrueRate',
        'SpendTime',
        'UserHost',
        'GroupNO',
    ];

}
