<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    public $timestamps = false;
    protected $table = 'major';
    protected $primaryKey = 'CourseNO';
    protected $fillable = [
        'MemberID',
        'SeatNO',
        'Assistant',
        'AverageScore',
        'Judgment',
        'StudentID',
        'GroupNO',
        'GroupName',
        'RemoteNO',
        'GrpMemberNO',
        'action',
        'flag',
    ];

}
