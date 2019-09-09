<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teachomework extends Model
{
    public $timestamps = false;
    protected $table = 'teahomework';
    protected $primaryKey = 'HomeworkNO';
    protected $fillable = [
        'MemberID',
        'ClassID',
        'HomeworkType',
        'HomeworkTitle',
        'CourseName',
        'BeginTime',
        'DateLine',
        'HomeMode',
        'Format',
        'HomeworkView',
        'HomeworkReview',
        'Description',
        'SendHomework',
    ];

    public function Course()
    {
        return $this->belongsTo(Course::class,'ClassID','CourseON');
    }
}
