<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $table = 'exercise';
    protected $primaryKey = 'ExNO';
    protected $fillable = [
        'MemberID',
        'CourseNO',
        'ClassID',
        'TPID',
        'ExType',
        'ExMode',
        'ExName',
        'ExLink',
        'ExTime',
        'EndTime',
        'AvgScore',
        'QNumber',
        'StuCount',
        'AnsNum',
        'TrueNum',
        'TrueRate',
        'TotalSpendTime',
        'AvgSpendTime',
        'Rule',
        'Status',
        'ExNORec',
        'FilePath',
        'SERIALNUMBER',
        'Description',
        'ReportTitle',
        'ReportSubject',
        'ReportGrade',
        'ReportTestName',
        'ReportTestDate',
        'tba_id',
    ];

    public function Course()
    {
        return $this->belongsTo(Course::class, 'CourseNO');
    }
}
