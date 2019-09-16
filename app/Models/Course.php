<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $table = 'course';
    protected $primaryKey = 'CourseNO';

    protected $fillable = [
        'MemberID',
        'ClassID',
        'CNO',
        'SNO',
        'SchoolID',
        'CourseCode',
        'CourseName',
        'departmentcode',
        'CourseCount',
        'CourseBTime',
        'Type',
        'SubjectID',
        'Subject',
        'SsoGroupID',
        'CourseNO',
        'majorcode',
        'validdt',
        'manageby',
        'Type',
        'tmcourse',
    ];

    public function Member()
    {
        return $this->belongsTo(Member::class, 'MemberID');
    }

    public function Teachomework()
    {
        return $this->hasMany(Teachomework::class, 'ClassID', 'CourseNO');
    }
}
