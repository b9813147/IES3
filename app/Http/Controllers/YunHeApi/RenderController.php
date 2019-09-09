<?php

namespace App\Http\Controllers\YunHeApi;

use App\Models\District_schools;
use App\Models\Districts;
use App\Models\DistrictsAllSchool;
use App\Models\Semesterinfo;
use App\Services\YunHeInfoService;
use App\Supports\HashIdSupport;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;


// 設定給跨網域api 使用
header('Access-Control-ALLow-Origin:*');
// 设置允许的响应类型
header('Access-Control-ALLow-Methods:GET, POST, PATCH, PUT, OPTIONS');
// 设置允许的响应头
header('Access-Control-ALLow-Headers:x-requested-with,content-type');

class RenderController extends Controller
{
    use HashIdSupport;

    private $yunHeInfoService;

    public function __construct(YunHeInfoService $yunHeInfoService)
    {
        $this->yunHeInfoService = $yunHeInfoService;
    }

    public function menu()
    {

        $years = Semesterinfo::query()->select('AcademicYear', 'SOrder')->where('AcademicYear', '>=', 2016)->get();

        foreach ($years as $key => $year) {
            if ($year->SOrder != 1) {
                $time[] = [
                    'key'   => 0,
                    'value' => $year->AcademicYear . '上半学期',
                    'year'  => $year->AcademicYear,
                ];
            } else {
                $time[] = [
                    'key'   => 1,
                    'value' => $year->AcademicYear . '下半学期',
                    'year'  => $year->AcademicYear,
                ];
            }
        }

        //学期时间列表
        $semesterList = [
            'state' => [
                'semesterlist' => [
                    "time" => $time
                ],
            ],
        ];


        return response()->json($semesterList);
    }

    public function getDistricts(Request $request)
    {
        $districtId = $this->decodeHashId($request->d);
        $semester   = $request->semester;
        $year       = $request->year;

        ($semester == null) ? $semester = 2 : (($semester == 0) ? $semester = 2 : $semester = 1);

        return ($districtId && $semester && $year)
            ? $this->getDistrictShow($districtId, $year, $semester)
            : $this->getDistrictsAll($districtId);
    }

    public function school(Request $request)
    {

        $districtId = $this->decodeHashId($request->d);
//        $districtId = $request->d;
        $schoolId = $request->schoolId;
        $semester = $request->semester;
        $year     = $request->year;

        ($semester == null) ? $semester = 2 : (($semester == 0) ? $semester = 2 : $semester = 1);
        ($year == null) ? $year = false : $year = true;

        ($schoolId) ? $schoolId : $schoolId = true;

        return ($districtId && $schoolId && $semester && $year)
            ? $this->getSchool($districtId, $request->schoolId, $semester, $request->year)
            : $this->getSchoolAll($districtId, $request->schoolId);

    }


    /**
     * @param int $districtId
     * @param int $schoolId
     * @param int $semester
     * @param int $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getSchool($districtId, $schoolId, $semester, $year)
    {
        ($semester == 2) ? $semester = 0 : $semester = 1;

        $monthArray = [];
        ($semester == 0)
            ? $monthArray = [[$year, 8], [$year, 9], [$year, 10], [$year, 11], [$year, 12], [$year + 1, 1]]
            : $monthArray = [[$year + 1, 2], [$year + 1, 3], [$year + 1, 4], [$year + 1, 5], [$year + 1, 6], [$year + 1, 7]];


        $schoolInfo = [];
        //schoolName, schoolId, schoolCode
        $select = '';
        $select = 's.SchoolID as schoolid, s.SchoolName as schoolname, s.Abbr as schoolcode';
        //檢查此學校存不存在
        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)
            ->where('s.SchoolID', $schoolId)
            ->exists();

        if (!$schoolInfo) {
            return '此學區無此學校';
        }

        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)
            ->where('s.SchoolID', $schoolId)
            ->first();


        $schoolInfo->marked             = 1;
        $schoolInfo->teachuserusing     = (object)[];
        $schoolInfo->studyuserusing     = (object)[];
        $schoolInfo->patriarchuserusing = (object)[];
        $schoolInfo->courseusing        = (object)[];
        $schoolInfo->teachnum           = (object)[];
        $schoolInfo->studynum           = (object)[];

        $schoolInfo->course    = (object)[];
        $schoolInfo->dashboard = (object)[];
        $schoolInfo->study     = (object)[];


        //teachNum 、studyNum、patriarchNum
        $data   = [];
        $select = '';
        $select = ' sum(teacherCount) as teacherCount,
                    sum(studentCount) as studentCount, 
                    sum(courseCount) as courseCount';
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('school_id', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    })
                    ->first();
                break;
            case 1:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->first();
                break;
        }


        $schoolInfo->teachuserusing->teachnum         = $data->teacherCount;
        $schoolInfo->teachuserusing->data             = (object)[];
        $schoolInfo->studyuserusing->studynum         = $data->studentCount;
        $schoolInfo->studyuserusing->data             = (object)[];
        $schoolInfo->patriarchuserusing->patriarchnum = 0;
        $schoolInfo->patriarchuserusing->data         = (object)[];
        $schoolInfo->courseusing->coursenum           = 0;


        //teachTotal 、studentTotal、parentTotal
        $data   = [];
        $select = '';
        $select = 'targetYear,targetMonth, sum(teacherLoginTimes) as teachlogin, sum(studentLoginTimes) as studylogin';

        $schoolInfo->teachuserusing->data->teachtotal      = 0;
        $schoolInfo->studyuserusing->data->studenttotal    = 0;
        $schoolInfo->patriarchuserusing->data->parenttotal = 0;;
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('school_id', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }
        $yearArray_index = 0;
        foreach ($data as $val) {

            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($val->targetYear * 100 + $val->targetMonth)) {
                $schoolInfo->teachuserusing->data->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->teachuserusing->data->data[] = 0;

                $schoolInfo->studyuserusing->data->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->studyuserusing->data->data[] = 0;

                $schoolInfo->patriarchuserusing->data->time [] = 0;
                $schoolInfo->patriarchuserusing->data->data [] = 0;

                $yearArray_index++;
            }

            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($val->targetYear * 100 + $val->targetMonth)) {
                $yearArray_index++;
            }

            $schoolInfo->teachuserusing->data->time[]     = $val->targetYear . "-" . $val->targetMonth;
            $schoolInfo->teachuserusing->data->data[]     = $val->teachlogin;
            $schoolInfo->teachuserusing->data->teachtotal += $val->teachlogin;

            $schoolInfo->studyuserusing->data->time[]       = $val->targetYear . "-" . $val->targetMonth;
            $schoolInfo->studyuserusing->data->data[]       = $val->studylogin;
            $schoolInfo->studyuserusing->data->studenttotal += $val->studylogin;

            $schoolInfo->patriarchuserusing->data->time [] = $val->targetYear . "-" . $val->targetMonth;;
            $schoolInfo->patriarchuserusing->data->data []     = 0;
            $schoolInfo->patriarchuserusing->data->parenttotal += 0;
        }

        // teachNum
        $schoolInfo->teachnum->teachnum = 0;
        $data                           = [];
        $select                         = '';
        $select                         = '(sum(t.sokratesCount)+
                    sum(t.enoteCount)+
                    sum(t.cmsCount)+
                    sum(t.hiTeachCount)+
                    sum(t.omrCount)+
                    sum(t.eventCount)+
                    sum(t.loginScoreCount)+
                    sum(t.combineCount)+
                    sum(t.assignmentCount)) as data,
                    t.member_id as teachId,
                    m.RealName as teach';
//        $data                           = $this->teach_data($semester , $select , $schoolInfo , $year);
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->where('m.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('m.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('t.member_id')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->where('m.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('t.member_id')
                    ->get();
                break;
        }
        $schoolInfo->teachnum->teachnum = count($data);
        foreach ($data as $datum) {
            $schoolInfo->teachnum->teach[]   = $datum->teach;
            $schoolInfo->teachnum->data[]    = $datum->data;
            $schoolInfo->teachnum->teachid[] = $datum->teachId;
        }

        //studyNum
        $data   = [];
        $select = '';
        $select = 'targetYear,targetMonth,sum(studentLoginTimes) as data';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('school_data')
                    ->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('school_id', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('school_data')
                    ->selectRaw($select)
                    ->where('school_id', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }

        $schoolInfo->studynum->studytotal = 0;
        $yearArray_index                  = 0;
        foreach ($data as $datum) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $schoolInfo->studynum->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->studynum->data[] = 0;

                $yearArray_index++;
            }

            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }
            $schoolInfo->studynum->time[]     = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->studynum->data[]     = $datum->data;
            $schoolInfo->studynum->studytotal += $datum->data;
        }
        //course
        $data   = [];
        $select = '';
        $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('d.course_no')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('d.course_no')
                    ->get();
                break;
        }
        $schoolInfo->courseusing->coursenum = count($data);
        foreach ($data as $datum) {
            $schoolInfo->course->curriculums[]   = $datum->CourseName;
            $schoolInfo->course->data[]          = $datum->data;
            $schoolInfo->course->curriculumsid[] = $datum->course_no;

        }

        $schoolInfo->dashboard->smartclasstable = (object)[];

        //dashboard
        $schoolInfo->dashboard->smartclasstable->electronicalnote = 0;
        $schoolInfo->dashboard->smartclasstable->uploadmovie      = 0;
        $schoolInfo->dashboard->smartclasstable->production       = 0;
        $schoolInfo->dashboard->smartclasstable->overturnclass    = 0;
        $schoolInfo->dashboard->smartclasstable->testissue        = 0;
        $schoolInfo->dashboard->smartclasstable->sokratestotal    = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess  = (object)[];

        //dashboard->smartclasstable->learningprocess
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;


        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->first();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->first();
                break;
        }
        $schoolInfo->dashboard->smartclasstable->electronicalnote                       = $data->electronicalnote;
        $schoolInfo->dashboard->smartclasstable->uploadmovie                            = $data->uploadmovie;
        $schoolInfo->dashboard->smartclasstable->production                             = $data->production;
        $schoolInfo->dashboard->smartclasstable->overturnclass                          = $data->overturnclass;
        $schoolInfo->dashboard->smartclasstable->testissue                              = $data->onlinetest;
        $schoolInfo->dashboard->smartclasstable->sokratestotal                          = $data->sokratestotal;
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

        //chartData
        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.assignmentCount) as onlinetest,
                   targetYear,targetMonth';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }
        $yearArray_index                      = 0;
        $sokratestotal['sokratestotal']       = 0;
        $electronicalnote['electronicalnote'] = 0;
        $uploadmovie['uploadmovie']           = 0;
        $production['production']             = 0;
        $overturnclass['overturnclass']       = 0;
        $testissue['testissue']               = 0;

        foreach ($data as $datum) {

            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $sokratestotal['title']     = '蘇格拉底';
                $sokratestotal['num']       = 1;
                $sokratestotal['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $sokratestotal['data'][]    = 0;
                $electronicalnote['title']  = '電子筆記';
                $electronicalnote['num']    = 2;
                $electronicalnote['time'][] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $electronicalnote['data'][] = 0;
                $uploadmovie['title']       = '上傳影片';
                $uploadmovie['num']         = 3;
                $uploadmovie['time'][]      = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $uploadmovie['data'][]      = 0;
                $production['title']        = '作業作品';
                $production['num']          = 4;
                $production['time'][]       = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $production['data'][]       = 0;
                $overturnclass['title']     = '翻轉課堂';
                $overturnclass['num']       = 5;
                $overturnclass['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $overturnclass['data'][]    = 0;
                $testissue['title']         = '測驗發布';
                $testissue['num']           = 6;
                $testissue['time'][]        = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $testissue['data'][]        = 0;
                $yearArray_index++;
            }

            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }


            $sokratestotal['title']               = '蘇格拉底';
            $sokratestotal['num']                 = 1;
            $sokratestotal['time'][]              = $datum->targetYear . "-" . $datum->targetMonth;
            $sokratestotal['data'][]              = $datum->sokratestotal;
            $sokratestotal['sokratestotal']       += $datum->sokratestotal;
            $electronicalnote['title']            = '電子筆記';
            $electronicalnote['num']              = 2;
            $electronicalnote['time'][]           = $datum->targetYear . "-" . $datum->targetMonth;
            $electronicalnote['data'][]           = $datum->electronicalnote;
            $electronicalnote['electronicalnote'] += $datum->electronicalnote;
            $uploadmovie['title']                 = '上傳影片';
            $uploadmovie['num']                   = 3;
            $uploadmovie['time'][]                = $datum->targetYear . "-" . $datum->targetMonth;
            $uploadmovie['data'][]                = $datum->uploadmovie;
            $uploadmovie['uploadmovie']           += $datum->uploadmovie;
            $production['title']                  = '作業作品';
            $production['num']                    = 4;
            $production['time'][]                 = $datum->targetYear . "-" . $datum->targetMonth;
            $production['data'][]                 = $datum->production;
            $production['production']             += $datum->production;
            $overturnclass['title']               = '翻轉課堂';
            $overturnclass['num']                 = 5;
            $overturnclass['time'][]              = $datum->targetYear . "-" . $datum->targetMonth;
            $overturnclass['data'][]              = $datum->overturnclass;
            $overturnclass['overturnclass']       += $datum->overturnclass;
            $testissue['title']                   = '測驗發布';
            $testissue['num']                     = 6;
            $testissue['time'][]                  = $datum->targetYear . "-" . $datum->targetMonth;
            $testissue['data'][]                  = $datum->onlinetest;
            $testissue['testissue']               += $datum->onlinetest;
        }

        //gradeName
        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.assignmentCount) as onlinetest,
                   gradeName';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('gradeName')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('gradeName')
                    ->get();
                break;
        }

        $sokratestotal['grade']->data[]    = 0;
        $electronicalnote['grade']->data[] = 0;
        $uploadmovie['grade']->data[]      = 0;
        $production['grade']->data[]       = 0;
        $overturnclass['grade']->data[]    = 0;
        $testissue['grade']->data[]        = 0;
        $grade                             = 1;
        foreach ($data as $datum) {
            while ($grade < $datum->gradeName) {
                $sokratestotal['grade']->time[]    = $grade;
                $sokratestotal['grade']->data[]    = 0;
                $electronicalnote['grade']->time[] = $grade;
                $electronicalnote['grade']->data[] = 0;
                $uploadmovie['grade']->time[]      = $grade;
                $uploadmovie['grade']->data[]      = 0;
                $production['grade']->time[]       = $grade;
                $production['grade']->data[]       = 0;
                $overturnclass['grade']->time[]    = $grade;
                $overturnclass['grade']->data[]    = 0;
                $testissue['grade']->time[]        = $grade;
                $testissue['grade']->data[]        = 0;
                $grade++;
            }
            if ($grade == $datum->gradeName) {
                $sokratestotal['grade']->time[]    = $datum->gradeName;
                $sokratestotal['grade']->data[]    += $datum->sokratestotal;
                $electronicalnote['grade']->time[] = $datum->gradeName;
                $electronicalnote['grade']->data[] += $datum->electronicalnote;
                $uploadmovie['grade']->time[]      = $datum->gradeName;
                $uploadmovie['grade']->data[]      += $datum->uploadmovie;
                $production['grade']->time[]       = $datum->gradeName;
                $production['grade']->data[]       += $datum->production;
                $overturnclass['grade']->time[]    = $datum->gradeName;
                $overturnclass['grade']->data[]    += $datum->overturnclass;
                $testissue['grade']->time[]        = $datum->gradeName;
                $testissue['grade']->data[]        += $datum->onlinetest;
                $grade++;
            }
        }

        array_shift($sokratestotal['grade']->data);
        array_shift($electronicalnote['grade']->data);
        array_shift($uploadmovie['grade']->data);
        array_shift($production['grade']->data);
        array_shift($overturnclass['grade']->data);
        array_shift($testissue['grade']->data);

        $schoolInfo->dashboard->smartclasstable->chartdata[] = $sokratestotal;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $electronicalnote;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $uploadmovie;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $production;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $overturnclass;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $testissue;


        // resource

        $schoolInfo->dashboard->smartclasstable->resource              = (object)[];
        $schoolInfo->dashboard->smartclasstable->resource->personage   = 0;
        $schoolInfo->dashboard->smartclasstable->resource->areashare   = 0;
        $schoolInfo->dashboard->smartclasstable->resource->schoolshare = 0;

        $select = '';
        $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear,targetMonth';

        $data = [];
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->where('m.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('m.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->where('m.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }

        $yearArray_index       = 0;
        $subjectnum['num']     = 0;
        $examinationnum['num'] = 0;
        $textbooknum['num']    = 0;
        foreach ($data as $datum) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $schoolInfo->dashboard->smartclasstable->resource->personage   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->areashare   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->schoolshare = 0;

                $subjectnum['id']         = 1;
                $subjectnum['time'][]     = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $subjectnum['data'][]     = 0;
                $examinationnum['id']     = 2;
                $examinationnum['time'][] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $examinationnum['data'][] = 0;
                $textbooknum['id']        = 3;
                $textbooknum['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $textbooknum['data'][]    = 0;

                $yearArray_index++;
            }
            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }
            $schoolInfo->dashboard->smartclasstable->resource->personage   += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $schoolInfo->dashboard->smartclasstable->resource->areashare   += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $schoolInfo->dashboard->smartclasstable->resource->schoolshare += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;

            $subjectnum['id']         = 1;
            $subjectnum['num']        += $datum->testItemCount;
            $subjectnum['time'][]     = $datum->targetYear . "-" . $datum->targetMonth;
            $subjectnum['data'][]     = $datum->testItemCount;
            $examinationnum['id']     = 2;
            $examinationnum['num']    += $datum->testPaperCount;
            $examinationnum['time'][] = $datum->targetYear . "-" . $datum->targetMonth;
            $examinationnum['data'][] = $datum->testPaperCount;
            $textbooknum['id']        = 3;
            $textbooknum['num']       += $datum->resourceCount;
            $textbooknum['time'][]    = $datum->targetYear . "-" . $datum->targetMonth;
            $textbooknum['data'][]    = $datum->resourceCount;

        }
        $schoolInfo->dashboard->smartclasstable->resource->subjectnum[]     = $subjectnum;
        $schoolInfo->dashboard->smartclasstable->resource->examinationnum[] = $examinationnum;
        $schoolInfo->dashboard->smartclasstable->resource->textbooknum[]    = $textbooknum;

        //study
//        $schoolInfo->study->underway   = (object)[];
//        $schoolInfo->study->unfinished = (object)[];
//        $schoolInfo->study->achieve    = (object)[];
        $schoolInfo->study->underway['percentage'] = 0;
        $schoolInfo->study->underway['id']         = 0;
//        $schoolInfo->study->underway['time'][]       = 0;
//        $schoolInfo->study->underway['data'][]       = 0;
        $schoolInfo->study->unfinished['percentage'] = 0;
        $schoolInfo->study->unfinished['id']         = 1;
//        $schoolInfo->study->unfinished['time'][]     = 0;
//        $schoolInfo->study->unfinished['data'][]     = 0;
        $schoolInfo->study->achieve['percentage'] = 0;
        $schoolInfo->study->achieve['id']         = 2;
//        $schoolInfo->study->achieve['time'][]        = 0;
//        $schoolInfo->study->achieve['data'][]        = 0;


        $data   = [];
        $select = '';
        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear,targetMonth';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetYear', $year + 1);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }
        $stunum          = 0;
        $underway        = 0;
        $unfinished      = 0;
        $achieve         = 0;
        $testingnum      = 0;
        $testingcount    = 0;
        $homeworknum     = 0;
        $homeworkcount   = 0;
        $yearArray_index = 0;

        foreach ($data as $datum) {
            while ($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1] < $datum->targetYear * 100 + $datum->targetMonth) {
                $schoolInfo->study->underway['time'][]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->underway['data'][]   = 0;
                $schoolInfo->study->unfinished['time'][] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->unfinished['data'][] = 0;
                $schoolInfo->study->achieve['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->achieve['data'][]    = 0;
                $yearArray_index++;
            }
            if ($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1] == $datum->targetYear * 100 + $datum->targetMonth) {
                $yearArray_index++;
            }


            $schoolInfo->study->underway['time'][]   = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->underway['data'][]   = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;
            $schoolInfo->study->unfinished['time'][] = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->unfinished['data'][] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
            $schoolInfo->study->achieve['time'][]    = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->achieve['data'][]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;

            $stunum        += $datum->fcEventStuNum;
            $underway      += $datum->fcEventStuInProgress;
            $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
            $achieve       += $datum->fcEventStuCount;
            $testingnum    += $datum->testingStuNum;
            $testingcount  += $datum->testingStuCount;
            $homeworknum   += $datum->homeworkStuNum;
            $homeworkcount += $datum->homeworkStuCount;
        }

        $data   = [];
        $select = '';
        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   gradeName';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($year, $schoolInfo) {
                        $q->where('targetYear', $year + 1)->where('c.SchoolID', $schoolInfo->schoolid);
                        $q->where('targetMonth', 1);
                    }
                    )
                    ->groupBy('gradeName')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->where('c.SchoolID', $schoolInfo->schoolid)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('gradeName')
                    ->get();
                break;
        }

        $grade = 1;
        foreach ($data as $datum) {

            while ($grade < $datum->gradeName) {
                $schoolInfo->study->underway['grade']->time[]   = $grade;
                $schoolInfo->study->underway['grade']->data[]   = 0;
                $schoolInfo->study->unfinished['grade']->time[] = $grade;
                $schoolInfo->study->unfinished['grade']->data[] = 0;
                $schoolInfo->study->achieve['grade']->time[]    = $grade;
                $schoolInfo->study->achieve['grade']->data[]    = 0;
                $grade++;
            }

            if ($grade == $datum->gradeName) {
                $schoolInfo->study->underway['grade']->time[]   = $datum->gradeName;
                $schoolInfo->study->underway['grade']->data[]   = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo->study->unfinished['grade']->time[] = $datum->gradeName;
                $schoolInfo->study->unfinished['grade']->data[] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo->study->achieve['grade']->time[]    = $datum->gradeName;
                $schoolInfo->study->achieve['grade']->data[]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;
                $grade++;
            }
        }

        $schoolInfo->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
        $schoolInfo->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
        $schoolInfo->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
        $schoolInfo->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
        $schoolInfo->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;

        return \Response::json($schoolInfo);

    }

    /**
     * @param int $districtId
     * @param int $schoolId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getSchoolAll($districtId, $schoolId)
    {

        $endYear = date('Y');
        for ($year = 2016; $year <= $endYear; $year++) {
            $yearTotal[] = $year;
        }
        //預設時間起始
        $year = 2016;

        //schoolName, schoolId, schoolCode
        $select = '';
        $select = 's.SchoolID as schoolid, s.SchoolName as schoolname, s.Abbr as schoolcode';
        //檢查此學校存不存在
        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)
            ->where('s.SchoolID', $schoolId)
            ->exists();

        if (!$schoolInfo) {
            return '此學區無此學校';
        }

        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)
            ->where('s.SchoolID', $schoolId)
            ->first();


        $schoolInfo->marked             = 1;
        $schoolInfo->teachuserusing     = (object)[];
        $schoolInfo->studyuserusing     = (object)[];
        $schoolInfo->patriarchuserusing = (object)[];
        $schoolInfo->courseusing        = (object)[];

        $schoolInfo->teachnum  = (object)[];
        $schoolInfo->studynum  = (object)[];
        $schoolInfo->course    = (object)[];
        $schoolInfo->dashboard = (object)[];
        $schoolInfo->study     = (object)[];


        //teachNum 、studyNum、counsehNum
        $data   = [];
        $select = '';
        $select = ' sum(teacherCount) as teacherCount,
                    sum(studentCount) as studentCount, 
                    sum(courseCount) as courseCount';

        $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
            ->where('school_id', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('school_id', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->first();

        $schoolInfo->teachuserusing->teachnum         = $data->teacherCount;
        $schoolInfo->teachuserusing->data             = (object)[];
        $schoolInfo->studyuserusing->studynum         = $data->studentCount;
        $schoolInfo->studyuserusing->data             = (object)[];
        $schoolInfo->patriarchuserusing->patriarchnum = 0;
        $schoolInfo->patriarchuserusing->data         = (object)[];
        $schoolInfo->courseusing->coursenum           = 0;
        //teachTotal 、studentTotal、parentTotal
        $data   = [];
        $select = '';
        $select = 'targetYear, sum(teacherLoginTimes) as teachlogin, sum(studentLoginTimes) as studylogin';

        $schoolInfo->teachuserusing->data->teachtotal   = 0;
        $schoolInfo->studyuserusing->data->studenttotal = 0;
        $schoolInfo->studyuserusing->data->parenttotal  = 0;


        $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
            ->where('school_id', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('school_id', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('targetYear')
            ->get();

        $index = 0;
        foreach ($data as $val) {
            while ($yearTotal[$index] < $val->targetYear) {
                $schoolInfo->teachuserusing->data->time[] = $yearTotal[$index];
                $schoolInfo->teachuserusing->data->data[] = 0;

                $schoolInfo->studyuserusing->data->time[] = $yearTotal[$index];
                $schoolInfo->studyuserusing->data->data[] = 0;

                $schoolInfo->patriarchuserusing->data->time [] = $yearTotal[$index];;
                $schoolInfo->patriarchuserusing->data->data [] = 0;

                $index++;
            }
            if ($yearTotal[$index] == $val->targetYear) {
                $index++;
            }

            $schoolInfo->teachuserusing->data->time[]     = $val->targetYear;
            $schoolInfo->teachuserusing->data->data[]     = $val->teachlogin;
            $schoolInfo->teachuserusing->data->teachtotal += $val->teachlogin;

            $schoolInfo->studyuserusing->data->time[]       = $val->targetYear;
            $schoolInfo->studyuserusing->data->data[]       = $val->studylogin;
            $schoolInfo->studyuserusing->data->studenttotal += $val->studylogin;

            $schoolInfo->patriarchuserusing->data->time [] = $val->targetYear;;
            $schoolInfo->patriarchuserusing->data->data [] = 0;
            $schoolInfo->studyuserusing->data->parenttotal += 0;
        }

        // teachNum
        $schoolInfo->teachnum->teachnum = 0;
        $data                           = [];
        $select                         = '';
        $select                         = '(sum(t.sokratesCount)+
                    sum(t.enoteCount)+
                    sum(t.cmsCount)+
                    sum(t.hiTeachCount)+
                    sum(t.omrCount)+
                    sum(t.eventCount)+
                    sum(t.loginScoreCount)+
                    sum(t.combineCount)+
                    sum(t.assignmentCount)) as data,
                    t.member_id as teachId,
                    m.RealName as teach';

        $data = \DB::connection('middle')->table('teacher_data as t')
            ->selectRaw($select)
            ->join('member as m', 'm.MemberID', 't.member_id')
            ->where('m.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('m.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('t.member_id')
            ->get();

        $schoolInfo->teachnum->teachnum = count($data);
        foreach ($data as $datum) {
            $schoolInfo->teachnum->teach[]   = $datum->teach;
            $schoolInfo->teachnum->data[]    = $datum->data;
            $schoolInfo->teachnum->teachid[] = $datum->teachId;
        }


        //studyNum
        $data   = [];
        $select = '';
        $select = 'targetYear,sum(studentLoginTimes) as data';

        $data = \DB::connection('middle')->table('school_data')
            ->selectRaw($select)
            ->where('school_id', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('school_id', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('targetYear')
            ->get();


        $schoolInfo->studynum->studytotal = 0;
        $index                            = 0;
        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $schoolInfo->studynum->time[] = $yearTotal[$index];
                $schoolInfo->studynum->data[] = $datum->data;

                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $schoolInfo->studynum->time[]     = $datum->targetYear;
            $schoolInfo->studynum->data[]     = $datum->data;
            $schoolInfo->studynum->studytotal += $datum->data;
        }

        //course
        $data   = [];
        $select = '';
        $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';

        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('d.course_no')
            ->get();

        $schoolInfo->courseusing->coursenum = count($data);

        foreach ($data as $datum) {
            $schoolInfo->course->curriculums[]   = $datum->CourseName;
            $schoolInfo->course->data[]          = $datum->data;
            $schoolInfo->course->curriculumsid[] = $datum->course_no;


        }


        $schoolInfo->dashboard->smartclasstable = (object)[];

        //dashboard
        $schoolInfo->dashboard->smartclasstable->electronicalnote = 0;
        $schoolInfo->dashboard->smartclasstable->uploadmovie      = 0;
        $schoolInfo->dashboard->smartclasstable->production       = 0;
        $schoolInfo->dashboard->smartclasstable->overturnclass    = 0;
        $schoolInfo->dashboard->smartclasstable->testissue        = 0;
        $schoolInfo->dashboard->smartclasstable->sokratestotal    = 0;

        $schoolInfo->dashboard->smartclasstable->learningprocess = (object)[];

        //dashboard->smartclasstable->learningprocess
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;

        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->first();

        $schoolInfo->dashboard->smartclasstable->electronicalnote                       = $data->electronicalnote;
        $schoolInfo->dashboard->smartclasstable->uploadmovie                            = $data->uploadmovie;
        $schoolInfo->dashboard->smartclasstable->production                             = $data->production;
        $schoolInfo->dashboard->smartclasstable->overturnclass                          = $data->overturnclass;
        $schoolInfo->dashboard->smartclasstable->testissue                              = $data->onlinetest;
        $schoolInfo->dashboard->smartclasstable->sokratestotal                          = $data->sokratestotal;
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

        //chartData
        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.assignmentCount) as onlinetest,
                   targetYear';


        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('targetYear')
            ->get();


        $sokratestotal['sokratestotal']       = 0;
        $electronicalnote['electronicalnote'] = 0;
        $uploadmovie['uploadmovie']           = 0;
        $production['production']             = 0;
        $overturnclass['overturnclass']       = 0;
        $testissue['testissue']               = 0;
        $index                                = 0;
        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $sokratestotal['title']     = '蘇格拉底';
                $sokratestotal['num']       = 1;
                $sokratestotal['time'][]    = $yearTotal[$index];
                $sokratestotal['data'][]    = 0;
                $electronicalnote['title']  = '電子筆記';
                $electronicalnote['num']    = 2;
                $electronicalnote['time'][] = $yearTotal[$index];
                $electronicalnote['data'][] = 0;
                $uploadmovie['title']       = '上傳影片';
                $uploadmovie['num']         = 3;
                $uploadmovie['time'][]      = $yearTotal[$index];
                $uploadmovie['data'][]      = 0;
                $production['title']        = '作業作品';
                $production['num']          = 4;
                $production['time'][]       = $yearTotal[$index];
                $production['data'][]       = 0;
                $overturnclass['title']     = '翻轉課堂';
                $overturnclass['num']       = 5;
                $overturnclass['time'][]    = $yearTotal[$index];
                $overturnclass['data'][]    = 0;
                $testissue['title']         = '測驗發布';
                $testissue['num']           = 6;
                $testissue['time'][]        = $yearTotal[$index];
                $testissue['data'][]        = 0;

                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $sokratestotal['title']               = '蘇格拉底';
            $sokratestotal['num']                 = 1;
            $sokratestotal['time'][]              = $datum->targetYear;
            $sokratestotal['data'][]              = $datum->sokratestotal;
            $sokratestotal['sokratestotal']       += $datum->sokratestotal;
            $electronicalnote['title']            = '電子筆記';
            $electronicalnote['num']              = 2;
            $electronicalnote['time'][]           = $datum->targetYear;
            $electronicalnote['data'][]           = $datum->electronicalnote;
            $electronicalnote['electronicalnote'] += $datum->electronicalnote;
            $uploadmovie['title']                 = '上傳影片';
            $uploadmovie['num']                   = 3;
            $uploadmovie['time'][]                = $datum->targetYear;
            $uploadmovie['data'][]                = $datum->uploadmovie;
            $uploadmovie['uploadmovie']           += $datum->uploadmovie;
            $production['title']                  = '作業作品';
            $production['num']                    = 4;
            $production['time'][]                 = $datum->targetYear;
            $production['data'][]                 = $datum->production;
            $production['production']             += $datum->production;
            $overturnclass['title']               = '翻轉課堂';
            $overturnclass['num']                 = 5;
            $overturnclass['time'][]              = $datum->targetYear;
            $overturnclass['data'][]              = $datum->overturnclass;
            $overturnclass['overturnclass']       += $datum->overturnclass;
            $testissue['title']                   = '測驗發布';
            $testissue['num']                     = 6;
            $testissue['time'][]                  = $datum->targetYear;
            $testissue['data'][]                  = $datum->onlinetest;
            $testissue['testissue']               += $datum->onlinetest;
        }

//        //gradeName
        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.assignmentCount) as onlinetest,
                   gradeName';


        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('gradeName')
            ->get();


        $sokratestotal['grade']->data[]    = 0;
        $electronicalnote['grade']->data[] = 0;
        $uploadmovie['grade']->data[]      = 0;
        $production['grade']->data[]       = 0;
        $overturnclass['grade']->data[]    = 0;
        $testissue['grade']->data[]        = 0;

        $grade = 1;
        foreach ($data as $datum) {
            while ($grade < $datum->gradeName) {
                $sokratestotal['grade']->time[]    = $grade;
                $sokratestotal['grade']->data[]    = 0;
                $electronicalnote['grade']->time[] = $grade;
                $electronicalnote['grade']->data[] = 0;
                $uploadmovie['grade']->time[]      = $grade;
                $uploadmovie['grade']->data[]      = 0;
                $production['grade']->time[]       = $grade;
                $production['grade']->data[]       = 0;
                $overturnclass['grade']->time[]    = $grade;
                $overturnclass['grade']->data[]    = 0;
                $testissue['grade']->time[]        = $grade;
                $testissue['grade']->data[]        = 0;
                $grade++;
            }

            if ($grade == $datum->gradeName) {
                $sokratestotal['grade']->time[]    = $datum->gradeName;
                $sokratestotal['grade']->data[]    += $datum->sokratestotal;
                $electronicalnote['grade']->time[] = $datum->gradeName;
                $electronicalnote['grade']->data[] += $datum->electronicalnote;
                $uploadmovie['grade']->time[]      = $datum->gradeName;
                $uploadmovie['grade']->data[]      += $datum->uploadmovie;
                $production['grade']->time[]       = $datum->gradeName;
                $production['grade']->data[]       += $datum->production;
                $overturnclass['grade']->time[]    = $datum->gradeName;
                $overturnclass['grade']->data[]    += $datum->overturnclass;
                $testissue['grade']->time[]        = $datum->gradeName;
                $testissue['grade']->data[]        += $datum->onlinetest;
                $grade++;
            }

        }
        array_shift($sokratestotal['grade']->data);
        array_shift($electronicalnote['grade']->data);
        array_shift($uploadmovie['grade']->data);
        array_shift($production['grade']->data);
        array_shift($overturnclass['grade']->data);
        array_shift($testissue['grade']->data);

        $schoolInfo->dashboard->smartclasstable->chartdata[] = $sokratestotal;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $electronicalnote;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $uploadmovie;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $production;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $overturnclass;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $testissue;

        // resource
        $schoolInfo->dashboard->smartclasstable->resource              = (object)[];
        $schoolInfo->dashboard->smartclasstable->resource->personage   = 0;
        $schoolInfo->dashboard->smartclasstable->resource->areashare   = 0;
        $schoolInfo->dashboard->smartclasstable->resource->schoolshare = 0;

        $select = '';
        $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear';

        $data = [];
        $data = \DB::connection('middle')->table('teacher_data as t')
            ->selectRaw($select)
            ->join('member as m', 'm.MemberID', 't.member_id')
            ->where('m.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('m.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('targetYear')
            ->get();


        $index                 = 0;
        $subjectnum['num']     = 0;
        $examinationnum['num'] = 0;
        $textbooknum['num']    = 0;
        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $schoolInfo->dashboard->smartclasstable->resource->personage   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->areashare   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->schoolshare = 0;

                $subjectnum['id']         = 1;
                $subjectnum['time'][]     = $yearTotal[$index];
                $subjectnum['data'][]     = 0;
                $examinationnum['id']     = 2;
                $examinationnum['time'][] = $yearTotal[$index];
                $examinationnum['data'][] = 0;
                $textbooknum['id']        = 3;
                $textbooknum['time'][]    = $yearTotal[$index];
                $textbooknum['data'][]    = 0;

                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $schoolInfo->dashboard->smartclasstable->resource->personage   += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $schoolInfo->dashboard->smartclasstable->resource->areashare   += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $schoolInfo->dashboard->smartclasstable->resource->schoolshare += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;

            $subjectnum['id']         = 1;
            $subjectnum['num']        += $datum->testItemCount;
            $subjectnum['time'][]     = $datum->targetYear;
            $subjectnum['data'][]     = $datum->testItemCount;
            $examinationnum['id']     = 2;
            $examinationnum['num']    += $datum->testPaperCount;
            $examinationnum['time'][] = $datum->targetYear;
            $examinationnum['data'][] = $datum->testPaperCount;
            $textbooknum['id']        = 3;
            $textbooknum['num']       += $datum->resourceCount;
            $textbooknum['time'][]    = $datum->targetYear;
            $textbooknum['data'][]    = $datum->resourceCount;

        }
        $schoolInfo->dashboard->smartclasstable->resource->subjectnum[]     = $subjectnum;
        $schoolInfo->dashboard->smartclasstable->resource->examinationnum[] = $examinationnum;
        $schoolInfo->dashboard->smartclasstable->resource->textbooknum[]    = $textbooknum;

        //study
        $schoolInfo->study->underway['percentage'] = 0;
        $schoolInfo->study->underway['id']         = 0;

        $schoolInfo->study->unfinished['percentage'] = 0;
        $schoolInfo->study->unfinished['id']         = 1;

        $schoolInfo->study->achieve['percentage'] = 0;
        $schoolInfo->study->achieve['id']         = 2;

        $data   = [];
        $select = '';
        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear';
        $data   = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('targetYear')
            ->get();

        $stunum        = 0;
        $underway      = 0;
        $unfinished    = 0;
        $achieve       = 0;
        $testingnum    = 0;
        $testingcount  = 0;
        $homeworknum   = 0;
        $homeworkcount = 0;
        $index         = 0;

        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $schoolInfo->study->underway['time'][]   = $yearTotal[$index];
                $schoolInfo->study->underway['data'][]   = 0;
                $schoolInfo->study->unfinished['time'][] = $yearTotal[$index];
                $schoolInfo->study->unfinished['data'][] = 0;
                $schoolInfo->study->achieve['time'][]    = $yearTotal[$index];
                $schoolInfo->study->achieve['data'][]    = 0;
                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $schoolInfo->study->underway['time'][]   = $datum->targetYear;
            $schoolInfo->study->underway['data'][]   = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;
            $schoolInfo->study->unfinished['time'][] = $datum->targetYear;
            $schoolInfo->study->unfinished['data'][] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
            $schoolInfo->study->achieve['time'][]    = $datum->targetYear;
            $schoolInfo->study->achieve['data'][]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;

            $stunum        += $datum->fcEventStuNum;
            $underway      += $datum->fcEventStuInProgress;
            $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
            $achieve       += $datum->fcEventStuCount;
            $testingnum    += $datum->testingStuNum;
            $testingcount  += $datum->testingStuCount;
            $homeworknum   += $datum->homeworkStuNum;
            $homeworkcount += $datum->homeworkStuCount;
        }

        $data   = [];
        $select = '';
        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   gradeName';

        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->where('c.SchoolID', $schoolInfo->schoolid)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($schoolInfo, $year) {
                $q->where('c.SchoolID', $schoolInfo->schoolid)->where('targetYear', '>', $year);
            })
            ->groupBy('gradeName')
            ->get();


        $grade = 1;
        foreach ($data as $datum) {
            while ($grade < $datum->gradeName) {
                $schoolInfo->study->underway['grade']->time[]   = $grade;
                $schoolInfo->study->underway['grade']->data[]   = 0;
                $schoolInfo->study->unfinished['grade']->time[] = $grade;
                $schoolInfo->study->unfinished['grade']->data[] = 0;
                $schoolInfo->study->achieve['grade']->time[]    = $grade;
                $schoolInfo->study->achieve['grade']->data[]    = 0;
                $grade++;
            }
            if ($grade == $datum->gradeName) {
                $schoolInfo->study->underway['grade']->time[] = $datum->gradeName;
                $schoolInfo->study->underway['grade']->data[] = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;;
                $schoolInfo->study->unfinished['grade']->time[] = $datum->gradeName;
                $schoolInfo->study->unfinished['grade']->data[] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo->study->achieve['grade']->time[]    = $datum->gradeName;
                $schoolInfo->study->achieve['grade']->data[]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;
                $grade++;
            }
        }

        $schoolInfo->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
        $schoolInfo->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
        $schoolInfo->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
        $schoolInfo->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
        $schoolInfo->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;


        return \Response::json($schoolInfo);

    }


    /**
     * @param $districtId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDistrictsAll($districtId)
    {

        $endYear = date('Y');
        for ($year = 2016; $year <= $endYear; $year++) {
            $yearTotal[] = $year;
        }

        //預設時間起始
        $year = 2016;

        $districtInfo = Districts::query()
            ->select('district_name')
            ->where('district_id', $districtId)
            ->get();

        $select     = 's.SchoolID as schoolid, s.SchoolName as schoolname, s.Abbr as schoolcode';
        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)->orderBy('s.SchoolID')->get();


        $count_schoolInfo = COUNT($schoolInfo);
        for ($schIndex = 0; $schIndex < $count_schoolInfo; $schIndex++) {
            $schoolInfo[$schIndex]->areaname     = $districtInfo[0]->district_name;
            $schoolInfo[$schIndex]->patriarchnum = 0;

            //teachNum、studentNum
            $select = '';
            $select = 'sum(teacherCount) as teacherCount,sum(studentCount) as studentCount';
            $data   = \DB::connection('middle')->table('school_data')->selectRaw($select)
                ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('school_id', $schoolInfo[$schIndex]->schoolid);
                })
                ->get();

            $schoolInfo[$schIndex]->teachnum     = $data[0]->teacherCount;
            $schoolInfo[$schIndex]->studentnum   = $data[0]->studentCount;
            $schoolInfo[$schIndex]->patriarchnum = 0;

            //course
            $data   = [];
            $select = '';
            $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';

            $data = \DB::connection('middle')->table('course_data as d')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'd.course_no')
                ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid);
                })
                ->groupBy('d.course_no')
                ->get();

            $schoolInfo[$schIndex]->curriculum = count($data);

            //teachTotal 、studentTotal、parentTotal
            $schoolInfo[$schIndex]->dashboard                                 = (object)[];
            $schoolInfo[$schIndex]->dashboard->teachlogintable                = (object)[];
            $schoolInfo[$schIndex]->dashboard->studylogintable                = (object)[];
            $schoolInfo[$schIndex]->dashboard->teachlogintable->num           = 1;
            $schoolInfo[$schIndex]->dashboard->teachlogintable->loginnum      = 0;
            $schoolInfo[$schIndex]->dashboard->studylogintable->num           = 1;
            $schoolInfo[$schIndex]->dashboard->studylogintable->studyloginnum = 0;

            $data   = [];
            $select = '';
            $select = 'targetYear, targetMonth, SUM(teacherLoginTimes) as teachlogin, SUM(studentLoginTimes) as studylogin';
            $data   = \DB::connection('middle')->table('school_data')->selectRaw($select)
                ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('school_id', $schoolInfo[$schIndex]->schoolid);
                })
                ->groupBy('targetYear')
                ->get();

            //dashboard
//            $schoolInfo[$schIndex]->dashboard->teachlogintable->loginnum   = 0;
//            $schoolInfo[$schIndex]->dashboard->studylogintable->studyloginnum = 0;
            $index = 0;
            foreach ($data as $val) {
                while ($yearTotal[$index] < $val->targetYear) {
                    $schoolInfo[$schIndex]->dashboard->teachlogintable->time[] = $yearTotal[$index];
                    $schoolInfo[$schIndex]->dashboard->teachlogintable->data[] = 0;

                    $schoolInfo[$schIndex]->dashboard->studylogintable->time[] = $yearTotal[$index];
                    $schoolInfo[$schIndex]->dashboard->studylogintable->data[] = 0;

                    $index++;
                }
                if ($yearTotal[$index] == $val->targetYear) {
                    $index++;
                }

                $schoolInfo[$schIndex]->dashboard->teachlogintable->time[]   = $val->targetYear;
                $schoolInfo[$schIndex]->dashboard->teachlogintable->data[]   = $val->teachlogin;
                $schoolInfo[$schIndex]->dashboard->teachlogintable->loginnum += $val->teachlogin;

                $schoolInfo[$schIndex]->dashboard->studylogintable->time[]        = $val->targetYear;
                $schoolInfo[$schIndex]->dashboard->studylogintable->data[]        = $val->studylogin;
                $schoolInfo[$schIndex]->dashboard->studylogintable->studyloginnum += $val->studylogin;
            }

            $schoolInfo[$schIndex]->dashboard->smartclasstable = (object)[];
            //dashboard->smartclasstable->schoolmessage
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage = (object)[];
            //dashboard->smartclasstable->schoolmessage->id
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->id               = $schoolInfo[$schIndex]->schoolid;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->electronicalnote = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->uploadmovie      = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->production       = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->overturnclass    = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->sokratestotal    = 0;

            //dashboard->smartclasstable->learningprocess
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess                        = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->analogytest           = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;


            $electronicalnote = (object)['title' => '电子笔记数', 'num' => '2', 'time' => [], 'data' => []];
            $uploadmovie      = (object)['title' => '上传影片数', 'num' => '3', 'time' => [], 'data' => []];
            $production       = (object)['title' => '作业作品数', 'num' => '4', 'time' => [], 'data' => []];
            $overturnclass    = (object)['title' => '翻转课堂数', 'num' => '5', 'time' => [], 'data' => []];
            $learningprocess  = (object)['title' => '学习历程', 'num' => '6', 'time' => [], 'data' => []];
            $sokratestotal    = (object)['title' => '苏格拉底', 'num' => '7', 'time' => [], 'data' => []];

            $data   = [];
            $select = '';
            $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

            $data = \DB::connection('middle')->table('course_data as d')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'd.course_no')
                ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid);
                })
                ->first();

            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->electronicalnote        = $data->electronicalnote;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->uploadmovie             = $data->uploadmovie;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->production              = $data->production;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->overturnclass           = $data->overturnclass;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->sokratestotal           = $data->sokratestotal;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

            //chartData
            $data   = [];
            $select = '';
            $select = 'd.targetYear, d.targetMonth, 
                    SUM(d.sokratesCount) as sokratestotal, 
                    SUM(d.enoteCount) as electronicalnote, 
                    SUM(d.cmsCount) as uploadmovie, 
                    SUM(d.homeworkCount) as production, 
                    SUM(d.fceventCount) as overturnclass, 
                    SUM(d.hiteachCount) as HiTeach, 
                    SUM(d.omrCount) as onlinechecking, 
                    SUM(d.eventCount) as interclasscompetition, 
                    SUM(d.loginScoreCount) as performancelogin, 
                    SUM(d.combineCount) as mergeactivity, 
                    SUM(d.assignmentCount) as onlinetest, 0 as analogytest';


            $data = \DB::connection('middle')->table('course_data as d')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'd.course_no')
                ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid);
                })
                ->groupBy('targetYear')
                ->get();


            $electronicalnote->electronicalnote = 0;
            $uploadmovie->uploadmovie           = 0;
            $production->production             = 0;
            $overturnclass->overturnclass       = 0;
            $learningprocess->learningprocess   = 0;
            $sokratestotal->sokratestotal       = 0;
            $index                              = 0;
            foreach ($data as $datum) {
                while ($yearTotal[$index] < $datum->targetYear) {
                    $sokratestotal->time[]    = $yearTotal[$index];
                    $sokratestotal->data[]    = 0;
                    $electronicalnote->time[] = $yearTotal[$index];
                    $electronicalnote->data[] = 0;
                    $uploadmovie->time[]      = $yearTotal[$index];
                    $uploadmovie->data[]      = 0;
                    $production->time[]       = $yearTotal[$index];
                    $production->data[]       = 0;
                    $overturnclass->time[]    = $yearTotal[$index];
                    $overturnclass->data[]    = 0;
                    $learningprocess->time[]  = $yearTotal[$index];
                    $learningprocess->data[]  = 0;

                    $index++;
                }
                if ($yearTotal[$index] == $datum->targetYear) {
                    $index++;
                }

                $sokratestotal->time[]        = $datum->targetYear;
                $sokratestotal->data[]        = $datum->sokratestotal;
                $sokratestotal->sokratestotal += $datum->sokratestotal;

                $electronicalnote->time[]           = $datum->targetYear;
                $electronicalnote->data[]           = $datum->electronicalnote;
                $electronicalnote->electronicalnote += $datum->electronicalnote;

                $uploadmovie->time[]      = $datum->targetYear;
                $uploadmovie->data[]      = $datum->uploadmovie;
                $uploadmovie->uploadmovie += $datum->uploadmovie;

                $production->time[]     = $datum->targetYear;
                $production->data[]     = $datum->production;
                $production->production += $datum->production;

                $overturnclass->time[]        = $datum->targetYear;
                $overturnclass->data[]        = $datum->overturnclass;
                $overturnclass->overturnclass += $datum->overturnclass;

                $learningprocess->time[]          = $datum->targetYear;
                $learningprocess->data[]          = 0;
                $learningprocess->learningprocess += $datum->HiTeach + $datum->onlinechecking + $datum->interclasscompetition + $datum->performancelogin + $datum->mergeactivity + $datum->onlinetest + $datum->analogytest;;
            }

            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $sokratestotal;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $electronicalnote;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $uploadmovie;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $production;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $overturnclass;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $learningprocess;

            //dashboard->smartclasstable->resource
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource                       = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->subject              = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->examination          = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->textbook             = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data                 = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum   = (object)['id' => 1, 'title' => '教师产生', 'num' => 0, 'time' => [], 'data' => []];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum   = (object)['id' => 2, 'title' => '区级分享', 'num' => 0, 'time' => [], 'data' => []];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum = (object)['id' => 3, 'title' => '校级分享', 'num' => 0, 'time' => [], 'data' => []];

            $select = '';
            $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear';
            $data   = [];

            $data = \DB::connection('middle')->table('teacher_data as t')
                ->selectRaw($select)
                ->join('member as m', 'm.MemberID', 't.member_id')
                ->where('m.SchoolID', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('m.SchoolID', $schoolInfo[$schIndex]->schoolid);
                })
                ->groupBy('targetYear')
                ->get();

            $index = 0;
            foreach ($data as $datum) {
                while ($yearTotal[$index] < $datum->targetYear) {
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->time[]   = $yearTotal[$index];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->data[]   = 0;
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $yearTotal[$index];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->data[]   = 0;
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $yearTotal[$index];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = 0;
                    $index++;
                }

                if ($yearTotal[$index] == $datum->targetYear) {
                    $index++;
                }

                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->subject                      += $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->examination                  += $datum->testPaperCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->textbook                     += $datum->resourceCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->time[]   = $datum->targetYear;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->data[]   = $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->num      += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $datum->targetYear;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->data[]   = $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->num      += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $datum->targetYear;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->num    += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
            }
            //study
            $schoolInfo[$schIndex]->study->underway['percentage']   = 0;
            $schoolInfo[$schIndex]->study->underway['id']           = 0;
            $schoolInfo[$schIndex]->study->underway['title']        = '进行中';
            $schoolInfo[$schIndex]->study->unfinished['percentage'] = 0;
            $schoolInfo[$schIndex]->study->unfinished['id']         = 1;
            $schoolInfo[$schIndex]->study->unfinished['title']      = '未完成';
            $schoolInfo[$schIndex]->study->achieve['percentage']    = 0;
            $schoolInfo[$schIndex]->study->achieve['id']            = 2;
            $schoolInfo[$schIndex]->study->achieve['title']         = '已完成';

            $data   = [];
            $select = '';

            $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear';
            $data   = \DB::connection('middle')->table('course_data as d')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'd.course_no')
                ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                ->where('targetYear', $year)
                ->whereBetween('targetMonth', [8, 12])
                ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                    $q->where('targetYear', '>', $year)->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid);
                })
                ->groupBy('targetYear')
                ->get();

            $stunum        = 0;
            $underway      = 0;
            $unfinished    = 0;
            $achieve       = 0;
            $testingnum    = 0;
            $testingcount  = 0;
            $homeworknum   = 0;
            $homeworkcount = 0;
            $index         = 0;
            foreach ($data as $datum) {
                while ($yearTotal[$index] < $datum->targetYear) {
                    $schoolInfo[$schIndex]->study->underway['time'][]   = $yearTotal[$index];
                    $schoolInfo[$schIndex]->study->underway['data'][]   = 0;
                    $schoolInfo[$schIndex]->study->unfinished['time'][] = $yearTotal[$index];
                    $schoolInfo[$schIndex]->study->unfinished['data'][] = 0;
                    $schoolInfo[$schIndex]->study->achieve['time'][]    = $yearTotal[$index];
                    $schoolInfo[$schIndex]->study->achieve['data'][]    = 0;
                    $index++;
                }
                if ($yearTotal[$index] == $datum->targetYear) {
                    $index++;
                }

                $schoolInfo[$schIndex]->study->underway['time'][]   = $datum->targetYear;
                $schoolInfo[$schIndex]->study->underway['data'][]   = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo[$schIndex]->study->unfinished['time'][] = $datum->targetYear;
                $schoolInfo[$schIndex]->study->unfinished['data'][] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo[$schIndex]->study->achieve['time'][]    = $datum->targetYear;
                $schoolInfo[$schIndex]->study->achieve['data'][]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;

                $stunum        += $datum->fcEventStuNum;
                $underway      += $datum->fcEventStuInProgress;
                $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
                $achieve       += $datum->fcEventStuCount;
                $testingnum    += $datum->testingStuNum;
                $testingcount  += $datum->testingStuCount;
                $homeworknum   += $datum->homeworkStuNum;
                $homeworkcount += $datum->homeworkStuCount;
            }
            $schoolInfo[$schIndex]->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
            $schoolInfo[$schIndex]->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;
        }

        $districtsAllSum = $this->districtsAllSum($districtId);

        $schoolInfo = $schoolInfo->prepend($districtsAllSum);

        return \Response::json($schoolInfo);
    }

    /**
     *
     * @param $districtId
     * @param $year
     * @param $semester
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDistrictShow($districtId, $year, $semester)
    {
        ($semester == 2) ? $semester = 0 : $semester = 1;

        ($semester == 0)
            ? $monthArray = [[$year, 8], [$year, 9], [$year, 10], [$year, 11], [$year, 12], [$year + 1, 1]]
            : $monthArray = [[$year + 1, 2], [$year + 1, 3], [$year + 1, 4], [$year + 1, 5], [$year + 1, 6], [$year + 1, 7]];

        $districtInfo = Districts::query()
            ->select('district_name')
            ->where('district_id', $districtId)
            ->get();

        $select     = 's.SchoolID as schoolid, s.SchoolName as schoolname, s.Abbr as schoolcode';
        $schoolInfo = \DB::table('schoolinfo as s')
            ->selectRaw($select)
            ->join('district_schools as d', 's.SchoolID', 'd.SchoolID')
            ->where('d.district_id', $districtId)->orderBy('s.SchoolID')->get();


        $count_schoolInfo = COUNT($schoolInfo);
        for ($schIndex = 0; $schIndex < $count_schoolInfo; $schIndex++) {
            $schoolInfo[$schIndex]->areaname     = $districtInfo[0]->district_name;
            $schoolInfo[$schIndex]->patriarchnum = 0;

            //teachNum、studentNum
            $select = '';
            $select = 'sum(teacherCount) as teacherCount,sum(studentCount) as studentCount';
            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                        ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('school_id', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->get();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                        ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->get();
                    break;
            }


            $schoolInfo[$schIndex]->teachnum     = $data[0]->teacherCount;
            $schoolInfo[$schIndex]->studentnum   = $data[0]->studentCount;
            $schoolInfo[$schIndex]->patriarchnum = 0;

            //course
            $data   = [];
            $select = '';
            $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';
            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->groupBy('d.course_no')
                        ->get();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->groupBy('d.course_no')
                        ->get();
                    break;
            }


            $schoolInfo[$schIndex]->curriculum = count($data);

            //teachTotal 、studentTotal、parentTotal
            $schoolInfo[$schIndex]->dashboard                                 = (object)[];
            $schoolInfo[$schIndex]->dashboard->teachlogintable                = (object)[];
            $schoolInfo[$schIndex]->dashboard->studylogintable                = (object)[];
            $schoolInfo[$schIndex]->dashboard->teachlogintable->num           = 1;
            $schoolInfo[$schIndex]->dashboard->teachlogintable->loginnum      = 0;
            $schoolInfo[$schIndex]->dashboard->studylogintable->num           = 1;
            $schoolInfo[$schIndex]->dashboard->studylogintable->studyloginnum = 0;

            $data   = [];
            $select = '';
            $select = 'targetYear, targetMonth, SUM(teacherLoginTimes) as teachlogin, SUM(studentLoginTimes) as studylogin';

            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                        ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('school_id', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);;
                        })
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
                case 1;
                    $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                        ->where('school_id', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
            }


            //dashboard
            $schoolInfo[$schIndex]->dashboard->teachlogintable->teachtotal   = 0;
            $schoolInfo[$schIndex]->dashboard->studylogintable->studenttotal = 0;

            $yearArray_index = 0;
            foreach ($data as $val) {
                while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($val->targetYear * 100 + $val->targetMonth)) {
                    $schoolInfo[$schIndex]->dashboard->teachlogintable->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->dashboard->teachlogintable->data[] = 0;

                    $schoolInfo[$schIndex]->dashboard->studylogintable->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->dashboard->studylogintable->data[] = 0;

                    $yearArray_index++;
                }
                if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($val->targetYear * 100 + $val->targetMonth)) {
                    $yearArray_index++;
                }

                $schoolInfo[$schIndex]->dashboard->teachlogintable->time[]     = $val->targetYear . "-" . $val->targetMonth;
                $schoolInfo[$schIndex]->dashboard->teachlogintable->data[]     = $val->teachlogin;
                $schoolInfo[$schIndex]->dashboard->teachlogintable->teachtotal += $val->teachlogin;

                $schoolInfo[$schIndex]->dashboard->studylogintable->time[]       = $val->targetYear . "-" . $val->targetMonth;
                $schoolInfo[$schIndex]->dashboard->studylogintable->data[]       = $val->studylogin;
                $schoolInfo[$schIndex]->dashboard->studylogintable->studenttotal += $val->studylogin;
            }


            $schoolInfo[$schIndex]->dashboard->smartclasstable = (object)[];
            //dashboard->smartclasstable->schoolmessage
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage = (object)[];
            //dashboard->smartclasstable->schoolmessage->id
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->id               = $schoolInfo[$schIndex]->schoolid;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->electronicalnote = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->uploadmovie      = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->production       = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->overturnclass    = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->sokratestotal    = 0;

            //dashboard->smartclasstable->learningprocess
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess                        = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->analogytest           = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;


            $electronicalnote = (object)['title' => '电子笔记数', 'num' => '2', 'time' => [], 'data' => []];
            $uploadmovie      = (object)['title' => '上传影片数', 'num' => '3', 'time' => [], 'data' => []];
            $production       = (object)['title' => '作业作品数', 'num' => '4', 'time' => [], 'data' => []];
            $overturnclass    = (object)['title' => '翻转课堂数', 'num' => '5', 'time' => [], 'data' => []];
            $learningprocess  = (object)['title' => '学习历程', 'num' => '6', 'time' => [], 'data' => []];
            $sokratestotal    = (object)['title' => '苏格拉底', 'num' => '7', 'time' => [], 'data' => []];

            $data   = [];
            $select = '';
            $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->first();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->first();
                    break;
            }


            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->electronicalnote        = $data->electronicalnote;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->uploadmovie             = $data->uploadmovie;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->production              = $data->production;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->overturnclass           = $data->overturnclass;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->schoolmessage->sokratestotal           = $data->sokratestotal;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

            //chartData
            $data   = [];
            $select = '';
            $select = 'd.targetYear, d.targetMonth, 
                    SUM(d.sokratesCount) as sokratestotal, 
                    SUM(d.enoteCount) as electronicalnote, 
                    SUM(d.cmsCount) as uploadmovie, 
                    SUM(d.homeworkCount) as production, 
                    SUM(d.fceventCount) as overturnclass, 
                    SUM(d.hiteachCount) as HiTeach, 
                    SUM(d.omrCount) as onlinechecking, 
                    SUM(d.eventCount) as interclasscompetition, 
                    SUM(d.loginScoreCount) as performancelogin, 
                    SUM(d.combineCount) as mergeactivity, 
                    SUM(d.assignmentCount) as onlinetest, 0 as analogytest';

            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
            }

            $electronicalnote->electronicalnote = 0;
            $uploadmovie->uploadmovie           = 0;
            $production->production             = 0;
            $overturnclass->overturnclass       = 0;
            $learningprocess->learningprocess   = 0;
            $sokratestotal->sokratestotal       = 0;
            $yearArray_index                    = 0;
            foreach ($data as $datum) {
                while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $sokratestotal->time[]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $sokratestotal->data[]    = 0;
                    $electronicalnote->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $electronicalnote->data[] = 0;
                    $uploadmovie->time[]      = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $uploadmovie->data[]      = 0;
                    $production->time[]       = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $production->data[]       = 0;
                    $overturnclass->time[]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $overturnclass->data[]    = 0;
                    $learningprocess->time[]  = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $learningprocess->data[]  = 0;

                    $yearArray_index++;
                }
                if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $yearArray_index++;
                }

                $sokratestotal->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $sokratestotal->data[]        = $datum->sokratestotal;
                $sokratestotal->sokratestotal += $datum->sokratestotal;

                $electronicalnote->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $electronicalnote->data[]           = $datum->electronicalnote;
                $electronicalnote->electronicalnote += $datum->electronicalnote;

                $uploadmovie->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $uploadmovie->data[]      = $datum->uploadmovie;
                $uploadmovie->uploadmovie += $datum->uploadmovie;

                $production->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $production->data[]     = $datum->production;
                $production->production += $datum->production;

                $overturnclass->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $overturnclass->data[]        = $datum->overturnclass;
                $overturnclass->overturnclass += $datum->overturnclass;

                $learningprocess->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
                $learningprocess->data[]          = 0;
                $learningprocess->learningprocess += $datum->HiTeach + $datum->onlinechecking + $datum->interclasscompetition + $datum->performancelogin + $datum->mergeactivity + $datum->onlinetest + $datum->analogytest;;
            }

            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $sokratestotal;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $electronicalnote;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $uploadmovie;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $production;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $overturnclass;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->chartdata[] = $learningprocess;

            //dashboard->smartclasstable->resource
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource                       = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->subject              = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->examination          = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->textbook             = 0;
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data                 = (object)[];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum   = (object)['id' => 1, 'title' => '教师产生', 'num' => 0, 'time' => [], 'data' => []];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum   = (object)['id' => 2, 'title' => '区级分享', 'num' => 0, 'time' => [], 'data' => []];
            $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum = (object)['id' => 3, 'title' => '校级分享', 'num' => 0, 'time' => [], 'data' => []];

            $select = '';
            $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear,targetMonth';
            $data   = [];

            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('teacher_data as t')
                        ->selectRaw($select)
                        ->join('member as m', 'm.MemberID', 't.member_id')
                        ->where('m.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('m.SchoolID', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('teacher_data as t')
                        ->selectRaw($select)
                        ->join('member as m', 'm.MemberID', 't.member_id')
                        ->where('m.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
            }

            $yearArray_index = 0;
            foreach ($data as $datum) {
                while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->time[]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->data[]   = 0;
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->data[]   = 0;
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = 0;
                    $yearArray_index++;
                }

                if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $yearArray_index++;
                }

                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->subject                      += $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->examination                  += $datum->testPaperCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->textbook                     += $datum->resourceCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->time[]   = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->data[]   = $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->personagenum->num      += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->data[]   = $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->areasharenum->num      += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
                $schoolInfo[$schIndex]->dashboard->smartclasstable->resource->data->schoolsharenum->num    += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
            }
            //study
            $schoolInfo[$schIndex]->study->underway['percentage']   = 0;
            $schoolInfo[$schIndex]->study->underway['id']           = 0;
            $schoolInfo[$schIndex]->study->underway['title']        = '进行中';
            $schoolInfo[$schIndex]->study->unfinished['percentage'] = 0;
            $schoolInfo[$schIndex]->study->unfinished['id']         = 1;
            $schoolInfo[$schIndex]->study->unfinished['title']      = '未完成';
            $schoolInfo[$schIndex]->study->achieve['percentage']    = 0;
            $schoolInfo[$schIndex]->study->achieve['id']            = 2;
            $schoolInfo[$schIndex]->study->achieve['title']         = '已完成';

            $data   = [];
            $select = '';

            $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear,targetMonth';
            switch ($semester) {
                case 0:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year)
                        ->whereBetween('targetMonth', [8, 12])
                        ->orWhere(function ($q) use ($schoolInfo, $schIndex, $year) {
                            $q->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                                ->where('targetYear', $year + 1)
                                ->where('targetMonth', 1);
                        })
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
                case 1:
                    $data = \DB::connection('middle')->table('course_data as d')
                        ->selectRaw($select)
                        ->join('course as c', 'c.CourseNO', 'd.course_no')
                        ->where('c.SchoolID', $schoolInfo[$schIndex]->schoolid)
                        ->where('targetYear', $year + 1)
                        ->whereBetween('targetMonth', [2, 7])
                        ->groupBy('targetYear', 'targetMonth')
                        ->get();
                    break;
            }

            $stunum          = 0;
            $underway        = 0;
            $unfinished      = 0;
            $achieve         = 0;
            $testingnum      = 0;
            $testingcount    = 0;
            $homeworknum     = 0;
            $homeworkcount   = 0;
            $yearArray_index = 0;
            foreach ($data as $datum) {
                while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $schoolInfo[$schIndex]->study->underway['time'][]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->study->underway['data'][]   = 0;
                    $schoolInfo[$schIndex]->study->unfinished['time'][] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->study->unfinished['data'][] = 0;
                    $schoolInfo[$schIndex]->study->achieve['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                    $schoolInfo[$schIndex]->study->achieve['data'][]    = 0;
                    $yearArray_index++;
                }
                if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                    $yearArray_index++;
                }

                $schoolInfo[$schIndex]->study->underway['time'][]   = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->study->underway['data'][]   = ($datum->fcEventStuNum) ? ($datum->fcEventStuInProgress / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo[$schIndex]->study->unfinished['time'][] = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->study->unfinished['data'][] = ($datum->fcEventStuNum) ? (($datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress)) / $datum->fcEventStuNum) * 100 : 0;
                $schoolInfo[$schIndex]->study->achieve['time'][]    = $datum->targetYear . "-" . $datum->targetMonth;
                $schoolInfo[$schIndex]->study->achieve['data'][]    = ($datum->fcEventStuNum) ? ($datum->fcEventStuCount / $datum->fcEventStuNum) * 100 : 0;

                $stunum        += $datum->fcEventStuNum;
                $underway      += $datum->fcEventStuInProgress;
                $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
                $achieve       += $datum->fcEventStuCount;
                $testingnum    += $datum->testingStuNum;
                $testingcount  += $datum->testingStuCount;
                $homeworknum   += $datum->homeworkStuNum;
                $homeworkcount += $datum->homeworkStuCount;
            }
            $schoolInfo[$schIndex]->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
            $schoolInfo[$schIndex]->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
            $schoolInfo[$schIndex]->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;
        }

        $all        = $this->districtsShowSum($districtId, $year, $semester);
        $schoolInfo = $schoolInfo->prepend($all);

        return \Response::json($schoolInfo);
    }

    /**
     * @param $districtId
     *
     * @return object
     */
    private function districtsAllSum($districtId)
    {
        $endYear = date('Y');
        for ($year = 2016; $year <= $endYear; $year++) {
            $yearTotal[] = $year;
        }

        //預設時間起始
        $year = 2016;

        $districtInfo = Districts::query()
            ->select('district_name')
            ->where('district_id', $districtId)
            ->get();

        $all_data = (object)[
            'areaname'   => $districtInfo[0]->district_name,
            'schoolname' => '所有学校',
            'schoolid'   => 'ALL',
            'schoolcode' => 'ALL',
        ];
        $query    = District_schools::query()
            ->select('SchoolID')
            ->where('district_id', $districtId)
            ->get();

        //teachNum、studentNum
        $select = '';
        $select = 'sum(teacherCount) as teacherCount,sum(studentCount) as studentCount';
        $data   = \DB::connection('middle')->table('school_data')->selectRaw($select)
            ->whereIn('school_id', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('school_id', $query);
            })
            ->first();


        $all_data->teachnum     = $data->teacherCount;
        $all_data->studentnum   = $data->studentCount;
        $all_data->patriarchnum = 0;


        //course
        $data   = [];
        $select = '';
        $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';

        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->whereIn('c.SchoolID', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('c.SchoolID', $query);
            })
            ->groupBy('d.course_no')
            ->get();

        $all_data->curriculum = count($data);

        //teachTotal 、studentTotal、parentTotal
        $all_data->dashboard                                 = (object)[];
        $all_data->dashboard->teachlogintable                = (object)[];
        $all_data->dashboard->studylogintable                = (object)[];
        $all_data->dashboard->teachlogintable->num           = 1;
        $all_data->dashboard->teachlogintable->loginnum      = 0;
        $all_data->dashboard->studylogintable->num           = 1;
        $all_data->dashboard->studylogintable->studyloginnum = 0;
        $data                                                = [];
        $select                                              = '';
        $select                                              = 'targetYear, targetMonth, SUM(teacherLoginTimes) as teachlogin, SUM(studentLoginTimes) as studylogin';

        $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
            ->whereIn('school_id', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('school_id', $query);
            })
            ->groupBy('targetYear')
            ->get();

        //dashboard
//        $all_data->dashboard->teachlogintable->teachtotal   = 0;
//        $all_data->dashboard->studylogintable->studenttotal = 0;
        $index = 0;
        foreach ($data as $val) {
            while ($yearTotal[$index] < $val->targetYear) {
                $all_data->dashboard->teachlogintable->time[] = $yearTotal[$index];
                $all_data->dashboard->teachlogintable->data[] = 0;

                $all_data->dashboard->studylogintable->time[] = $yearTotal[$index];
                $all_data->dashboard->studylogintable->data[] = 0;

                $index++;
            }
            if ($yearTotal[$index] == $val->targetYear) {
                $index++;
            }

            $all_data->dashboard->teachlogintable->time[]   = $val->targetYear;
            $all_data->dashboard->teachlogintable->data[]   = $val->teachlogin;
            $all_data->dashboard->teachlogintable->loginnum += $val->teachlogin;

            $all_data->dashboard->studylogintable->time[]        = $val->targetYear;
            $all_data->dashboard->studylogintable->data[]        = $val->studylogin;
            $all_data->dashboard->studylogintable->studyloginnum += $val->studylogin;
        }

        $all_data->dashboard->smartclasstable = (object)[];
        //dashboard->smartclasstable->schoolmessage
        $all_data->dashboard->smartclasstable->schoolmessage = (object)[];
        //dashboard->smartclasstable->schoolmessage->id
        $all_data->dashboard->smartclasstable->schoolmessage->id               = $all_data->schoolid;
        $all_data->dashboard->smartclasstable->schoolmessage->electronicalnote = 0;
        $all_data->dashboard->smartclasstable->schoolmessage->uploadmovie      = 0;
        $all_data->dashboard->smartclasstable->schoolmessage->production       = 0;
        $all_data->dashboard->smartclasstable->schoolmessage->overturnclass    = 0;
        $all_data->dashboard->smartclasstable->schoolmessage->sokratestotal    = 0;

        //dashboard->smartclasstable->learningprocess
        $all_data->dashboard->smartclasstable->learningprocess                        = (object)[];
        $all_data->dashboard->smartclasstable->learningprocess->analogytest           = 0;
        $all_data->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
        $all_data->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
        $all_data->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
        $all_data->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
        $all_data->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
        $all_data->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
        $all_data->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;


        $electronicalnote = (object)['title' => '电子笔记数', 'num' => '2', 'time' => [], 'data' => []];
        $uploadmovie      = (object)['title' => '上传影片数', 'num' => '3', 'time' => [], 'data' => []];
        $production       = (object)['title' => '作业作品数', 'num' => '4', 'time' => [], 'data' => []];
        $overturnclass    = (object)['title' => '翻转课堂数', 'num' => '5', 'time' => [], 'data' => []];
        $learningprocess  = (object)['title' => '学习历程', 'num' => '6', 'time' => [], 'data' => []];
        $sokratestotal    = (object)['title' => '苏格拉底', 'num' => '7', 'time' => [], 'data' => []];


        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->whereIn('c.SchoolID', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('c.SchoolID', $query);
            })
            ->first();

        $all_data->dashboard->smartclasstable->schoolmessage->electronicalnote        = $data->electronicalnote;
        $all_data->dashboard->smartclasstable->schoolmessage->uploadmovie             = $data->uploadmovie;
        $all_data->dashboard->smartclasstable->schoolmessage->production              = $data->production;
        $all_data->dashboard->smartclasstable->schoolmessage->overturnclass           = $data->overturnclass;
        $all_data->dashboard->smartclasstable->schoolmessage->sokratestotal           = $data->sokratestotal;
        $all_data->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
        $all_data->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
        $all_data->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
        $all_data->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
        $all_data->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
        $all_data->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
        $all_data->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
        $all_data->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

        //chartData
        $data   = [];
        $select = '';
        $select = 'd.targetYear, d.targetMonth, 
                    SUM(d.sokratesCount) as sokratestotal, 
                    SUM(d.enoteCount) as electronicalnote, 
                    SUM(d.cmsCount) as uploadmovie, 
                    SUM(d.homeworkCount) as production, 
                    SUM(d.fceventCount) as overturnclass, 
                    SUM(d.hiteachCount) as HiTeach, 
                    SUM(d.omrCount) as onlinechecking, 
                    SUM(d.eventCount) as interclasscompetition, 
                    SUM(d.loginScoreCount) as performancelogin, 
                    SUM(d.combineCount) as mergeactivity, 
                    SUM(d.assignmentCount) as onlinetest, 0 as analogytest';


        $data = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->whereIn('c.SchoolID', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('c.SchoolID', $query);
            })
            ->groupBy('targetYear')
            ->get();


        $electronicalnote->electronicalnote = 0;
        $uploadmovie->uploadmovie           = 0;
        $production->production             = 0;
        $overturnclass->overturnclass       = 0;
        $learningprocess->learningprocess   = 0;
        $sokratestotal->sokratestotal       = 0;
        $index                              = 0;
        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $sokratestotal->time[]    = $yearTotal[$index];
                $sokratestotal->data[]    = 0;
                $electronicalnote->time[] = $yearTotal[$index];
                $electronicalnote->data[] = 0;
                $uploadmovie->time[]      = $yearTotal[$index];
                $uploadmovie->data[]      = 0;
                $production->time[]       = $yearTotal[$index];
                $production->data[]       = 0;
                $overturnclass->time[]    = $yearTotal[$index];
                $overturnclass->data[]    = 0;
                $learningprocess->time[]  = $yearTotal[$index];
                $learningprocess->data[]  = 0;

                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $sokratestotal->time[]        = $datum->targetYear;
            $sokratestotal->data[]        = $datum->sokratestotal;
            $sokratestotal->sokratestotal += $datum->sokratestotal;

            $electronicalnote->time[]           = $datum->targetYear;
            $electronicalnote->data[]           = $datum->electronicalnote;
            $electronicalnote->electronicalnote += $datum->electronicalnote;

            $uploadmovie->time[]      = $datum->targetYear;
            $uploadmovie->data[]      = $datum->uploadmovie;
            $uploadmovie->uploadmovie += $datum->uploadmovie;

            $production->time[]     = $datum->targetYear;
            $production->data[]     = $datum->production;
            $production->production += $datum->production;

            $overturnclass->time[]        = $datum->targetYear;
            $overturnclass->data[]        = $datum->overturnclass;
            $overturnclass->overturnclass += $datum->overturnclass;

            $learningprocess->time[]          = $datum->targetYear;
            $learningprocess->data[]          = 0;
            $learningprocess->learningprocess += $datum->HiTeach + $datum->onlinechecking + $datum->interclasscompetition + $datum->performancelogin + $datum->mergeactivity + $datum->onlinetest + $datum->analogytest;;
        }

        $all_data->dashboard->smartclasstable->chartdata[] = $sokratestotal;
        $all_data->dashboard->smartclasstable->chartdata[] = $electronicalnote;
        $all_data->dashboard->smartclasstable->chartdata[] = $uploadmovie;
        $all_data->dashboard->smartclasstable->chartdata[] = $production;
        $all_data->dashboard->smartclasstable->chartdata[] = $overturnclass;
        $all_data->dashboard->smartclasstable->chartdata[] = $learningprocess;

        //dashboard->smartclasstable->resource
        $all_data->dashboard->smartclasstable->resource                       = (object)[];
        $all_data->dashboard->smartclasstable->resource->subject              = 0;
        $all_data->dashboard->smartclasstable->resource->examination          = 0;
        $all_data->dashboard->smartclasstable->resource->textbook             = 0;
        $all_data->dashboard->smartclasstable->resource->data                 = (object)[];
        $all_data->dashboard->smartclasstable->resource->data->personagenum   = (object)['id' => 1, 'title' => '教师产生', 'num' => 0, 'time' => [], 'data' => []];
        $all_data->dashboard->smartclasstable->resource->data->areasharenum   = (object)['id' => 2, 'title' => '区级分享', 'num' => 0, 'time' => [], 'data' => []];
        $all_data->dashboard->smartclasstable->resource->data->schoolsharenum = (object)['id' => 3, 'title' => '校级分享', 'num' => 0, 'time' => [], 'data' => []];


        $select = '';
        $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear';
        $data   = [];

        $data = \DB::connection('middle')->table('teacher_data as t')
            ->selectRaw($select)
            ->join('member as m', 'm.MemberID', 't.member_id')
            ->whereIn('m.SchoolID', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('m.SchoolID', $query);
            })
            ->groupBy('targetYear')
            ->get();

        $index = 0;
        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $all_data->dashboard->smartclasstable->resource->data->personagenum->time[]   = $yearTotal[$index];
                $all_data->dashboard->smartclasstable->resource->data->personagenum->data[]   = 0;
                $all_data->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $yearTotal[$index];
                $all_data->dashboard->smartclasstable->resource->data->areasharenum->data[]   = 0;
                $all_data->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $yearTotal[$index];
                $all_data->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = 0;
                $index++;
            }

            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }


            $all_data->dashboard->smartclasstable->resource->subject                      += $datum->testItemCount;
            $all_data->dashboard->smartclasstable->resource->examination                  += $datum->testPaperCount;
            $all_data->dashboard->smartclasstable->resource->textbook                     += $datum->resourceCount;
            $all_data->dashboard->smartclasstable->resource->data->personagenum->time[]   = $datum->targetYear;
            $all_data->dashboard->smartclasstable->resource->data->personagenum->data[]   = $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $all_data->dashboard->smartclasstable->resource->data->personagenum->num      += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $all_data->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $datum->targetYear;
            $all_data->dashboard->smartclasstable->resource->data->areasharenum->data[]   = $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $all_data->dashboard->smartclasstable->resource->data->areasharenum->num      += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $all_data->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $datum->targetYear;
            $all_data->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
            $all_data->dashboard->smartclasstable->resource->data->schoolsharenum->num    += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;

        }

        //study
        $all_data->study->underway['percentage']   = 0;
        $all_data->study->underway['id']           = 0;
        $all_data->study->underway['title']        = '进行中';
        $all_data->study->unfinished['percentage'] = 0;
        $all_data->study->unfinished['id']         = 1;
        $all_data->study->unfinished['title']      = '未完成';
        $all_data->study->achieve['percentage']    = 0;
        $all_data->study->achieve['id']            = 2;
        $all_data->study->achieve['title']         = '已完成';

        $data   = [];
        $select = '';

        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear';
        $data   = \DB::connection('middle')->table('course_data as d')
            ->selectRaw($select)
            ->join('course as c', 'c.CourseNO', 'd.course_no')
            ->whereIn('c.SchoolID', $query)
            ->where('targetYear', $year)
            ->whereBetween('targetMonth', [8, 12])
            ->orWhere(function ($q) use ($query, $year) {
                $q->where('targetYear', '>', $year)->whereIn('c.SchoolID', $query);
            })
            ->groupBy('targetYear')
            ->get();

        $stunum        = 0;
        $underway      = 0;
        $unfinished    = 0;
        $achieve       = 0;
        $testingnum    = 0;
        $testingcount  = 0;
        $homeworknum   = 0;
        $homeworkcount = 0;
        $index         = 0;

        foreach ($data as $datum) {
            while ($yearTotal[$index] < $datum->targetYear) {
                $all_data->study->underway['time'][]   = $yearTotal[$index];
                $all_data->study->underway['data'][]   = 0;
                $all_data->study->unfinished['time'][] = $yearTotal[$index];
                $all_data->study->unfinished['data'][] = 0;
                $all_data->study->achieve['time'][]    = $yearTotal[$index];
                $all_data->study->achieve['data'][]    = 0;
                $index++;
            }
            if ($yearTotal[$index] == $datum->targetYear) {
                $index++;
            }

            $all_data->study->underway['time'][]   = $datum->targetYear;
            $all_data->study->underway['data'][]   = ($datum->fcEventStuNum) ? $datum->fcEventStuInProgress / $datum->fcEventStuNum * 100 : 0;
            $all_data->study->unfinished['time'][] = $datum->targetYear;
            $all_data->study->unfinished['data'][] = ($datum->fcEventStuNum) ? $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress) / $datum->fcEventStuNum * 100 : 0;
            $all_data->study->achieve['time'][]    = $datum->targetYear;
            $all_data->study->achieve['data'][]    = ($datum->fcEventStuNum) ? $datum->fcEventStuCount / $datum->fcEventStuNum * 100 : 0;

            $stunum        += $datum->fcEventStuNum;
            $underway      += $datum->fcEventStuInProgress;
            $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
            $achieve       += $datum->fcEventStuCount;
            $testingnum    += $datum->testingStuNum;
            $testingcount  += $datum->testingStuCount;
            $homeworknum   += $datum->homeworkStuNum;
            $homeworkcount += $datum->homeworkStuCount;
        }
        $all_data->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
        $all_data->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
        $all_data->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
        $all_data->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
        $all_data->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;

        return $all_data;
    }

    /**
     * @param $districtId
     * @param $year
     * @param $semester
     *
     * @return object
     */
    private function districtsShowSum($districtId, $year, $semester)
    {

        ($semester == 0)
            ? $monthArray = [[$year, 8], [$year, 9], [$year, 10], [$year, 11], [$year, 12], [$year + 1, 1]]
            : $monthArray = [[$year + 1, 2], [$year + 1, 3], [$year + 1, 4], [$year + 1, 5], [$year + 1, 6], [$year + 1, 7]];

        $districtInfo = Districts::query()
            ->select('district_name')
            ->where('district_id', $districtId)
            ->get();


        $query = District_schools::query()
            ->select('SchoolID')
            ->where('district_id', $districtId)
            ->get();

        $schoolInfo = (object)[
            'areaname'   => $districtInfo[0]->district_name,
            'schoolname' => '所有学校',
            'schoolid'   => 'ALL',
            'schoolcode' => 'ALL',
        ];

        //teachNum、studentNum
        $select = '';
        $select = 'sum(teacherCount) as teacherCount,sum(studentCount) as studentCount';
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->whereIn('school_id', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('school_id', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->first();
                break;
            case 1:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->whereIn('school_id', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->first();
                break;
        }


        $schoolInfo->teachnum     = $data->teacherCount;
        $schoolInfo->studentnum   = $data->studentCount;
        $schoolInfo->patriarchnum = 0;

        $data   = [];
        $select = '';
        $select = '(sum(d.sokratesCount)+
                    sum(d.enoteCount)+
                    sum(d.cmsCount)+
                    sum(d.hiTeachCount)+
                    sum(d.omrCount)+
                    sum(d.eventCount)+
                    sum(d.loginScoreCount)+
                    sum(d.combineCount)+
                    sum(d.assignmentCount)) as data,
                    d.course_no,
                    c.CourseName';
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('c.SchoolID', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->groupBy('d.course_no')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('d.course_no')
                    ->get();
                break;
        }


        $schoolInfo->curriculum = count($data);

        //teachTotal 、studentTotal、parentTotal
        $schoolInfo->dashboard                                 = (object)[];
        $schoolInfo->dashboard->teachlogintable                = (object)[];
        $schoolInfo->dashboard->studylogintable                = (object)[];
        $schoolInfo->dashboard->teachlogintable->num           = 1;
        $schoolInfo->dashboard->teachlogintable->loginnum      = 0;
        $schoolInfo->dashboard->studylogintable->num           = 1;
        $schoolInfo->dashboard->studylogintable->studyloginnum = 0;

        $data   = [];
        $select = '';
        $select = 'targetYear, targetMonth, SUM(teacherLoginTimes) as teachlogin, SUM(studentLoginTimes) as studylogin';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->whereIn('school_id', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('school_id', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);;
                    })
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1;
                $data = \DB::connection('middle')->table('school_data')->selectRaw($select)
                    ->whereIn('school_id', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }


        //dashboard
//        $schoolInfo->dashboard->teachlogintable->teachtotal   = 0;
//        $schoolInfo->dashboard->studylogintable->studenttotal = 0;

        $yearArray_index = 0;
        foreach ($data as $val) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($val->targetYear * 100 + $val->targetMonth)) {
                $schoolInfo->dashboard->teachlogintable->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->dashboard->teachlogintable->data[] = 0;

                $schoolInfo->dashboard->studylogintable->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->dashboard->studylogintable->data[] = 0;

                $yearArray_index++;
            }
            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($val->targetYear * 100 + $val->targetMonth)) {
                $yearArray_index++;
            }

            $schoolInfo->dashboard->teachlogintable->time[]   = $val->targetYear . "-" . $val->targetMonth;
            $schoolInfo->dashboard->teachlogintable->data[]   = $val->teachlogin;
            $schoolInfo->dashboard->teachlogintable->loginnum += $val->teachlogin;

            $schoolInfo->dashboard->studylogintable->time[]        = $val->targetYear . "-" . $val->targetMonth;
            $schoolInfo->dashboard->studylogintable->data[]        = $val->studylogin;
            $schoolInfo->dashboard->studylogintable->studyloginnum += $val->studylogin;
        }


        $schoolInfo->dashboard->smartclasstable = (object)[];
        //dashboard->smartclasstable->schoolmessage
        $schoolInfo->dashboard->smartclasstable->schoolmessage = (object)[];
        //dashboard->smartclasstable->schoolmessage->id
        $schoolInfo->dashboard->smartclasstable->schoolmessage->id               = $schoolInfo->schoolid;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->electronicalnote = 0;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->uploadmovie      = 0;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->production       = 0;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->overturnclass    = 0;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->sokratestotal    = 0;

        //dashboard->smartclasstable->learningprocess
        $schoolInfo->dashboard->smartclasstable->learningprocess                        = (object)[];
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = 0;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = 0;


        $electronicalnote = (object)['title' => '电子笔记数', 'num' => '2', 'time' => [], 'data' => []];
        $uploadmovie      = (object)['title' => '上传影片数', 'num' => '3', 'time' => [], 'data' => []];
        $production       = (object)['title' => '作业作品数', 'num' => '4', 'time' => [], 'data' => []];
        $overturnclass    = (object)['title' => '翻转课堂数', 'num' => '5', 'time' => [], 'data' => []];
        $learningprocess  = (object)['title' => '学习历程', 'num' => '6', 'time' => [], 'data' => []];
        $sokratestotal    = (object)['title' => '苏格拉底', 'num' => '7', 'time' => [], 'data' => []];

        $data   = [];
        $select = '';
        $select = 'sum(d.sokratesCount) as sokratestotal,
                   sum(d.enoteCount) as electronicalnote,
                   sum(d.cmsCount) as uploadmovie,
                   sum(d.omrCount) as onlinechecking,
                   sum(d.homeworkCount) as production,
                   sum(d.fcEventCount) as overturnclass,
                   sum(d.eventCount) as interclasscompetition,
                   sum(d.hiTeachCount) as HiTeach,
                   sum(d.loginScoreCount) as performancelogin,
                   sum(d.combineCount) as mergeactivity,
                   sum(d.assignmentCount) as onlinetest,
                   0 as analogytest,
                   (sum(d.omrCount) +
                    sum(d.eventCount) +
                    sum(d.hiTeachCount) +
                    sum(d.loginScoreCount) +
                    sum(d.combineCount) +
                    0  +
                    sum(d.assignmentCount)) as alllearningprocess';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('c.SchoolID', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->first();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->first();
                break;
        }


        $schoolInfo->dashboard->smartclasstable->schoolmessage->electronicalnote        = $data->electronicalnote;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->uploadmovie             = $data->uploadmovie;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->production              = $data->production;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->overturnclass           = $data->overturnclass;
        $schoolInfo->dashboard->smartclasstable->schoolmessage->sokratestotal           = $data->sokratestotal;
        $schoolInfo->dashboard->smartclasstable->learningprocess->analogytest           = $data->analogytest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinetest            = $data->onlinetest;
        $schoolInfo->dashboard->smartclasstable->learningprocess->interclasscompetition = $data->interclasscompetition;
        $schoolInfo->dashboard->smartclasstable->learningprocess->HiTeach               = $data->HiTeach;
        $schoolInfo->dashboard->smartclasstable->learningprocess->performancelogin      = $data->performancelogin;
        $schoolInfo->dashboard->smartclasstable->learningprocess->mergeactivity         = $data->mergeactivity;
        $schoolInfo->dashboard->smartclasstable->learningprocess->onlinechecking        = $data->onlinechecking;
        $schoolInfo->dashboard->smartclasstable->learningprocess->alllearningprocess    = $data->alllearningprocess;

        //chartData
        $data   = [];
        $select = '';
        $select = 'd.targetYear, d.targetMonth, 
                    SUM(d.sokratesCount) as sokratestotal, 
                    SUM(d.enoteCount) as electronicalnote, 
                    SUM(d.cmsCount) as uploadmovie, 
                    SUM(d.homeworkCount) as production, 
                    SUM(d.fceventCount) as overturnclass, 
                    SUM(d.hiteachCount) as HiTeach, 
                    SUM(d.omrCount) as onlinechecking, 
                    SUM(d.eventCount) as interclasscompetition, 
                    SUM(d.loginScoreCount) as performancelogin, 
                    SUM(d.combineCount) as mergeactivity, 
                    SUM(d.assignmentCount) as onlinetest, 0 as analogytest';

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('c.SchoolID', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }

        $electronicalnote->electronicalnote = 0;
        $uploadmovie->uploadmovie           = 0;
        $production->production             = 0;
        $overturnclass->overturnclass       = 0;
        $learningprocess->learningprocess   = 0;
        $sokratestotal->sokratestotal       = 0;
        $yearArray_index                    = 0;
        foreach ($data as $datum) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $sokratestotal->time[]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $sokratestotal->data[]    = 0;
                $electronicalnote->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $electronicalnote->data[] = 0;
                $uploadmovie->time[]      = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $uploadmovie->data[]      = 0;
                $production->time[]       = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $production->data[]       = 0;
                $overturnclass->time[]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $overturnclass->data[]    = 0;
                $learningprocess->time[]  = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $learningprocess->data[]  = 0;

                $yearArray_index++;
            }
            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }

            $sokratestotal->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $sokratestotal->data[]        = $datum->sokratestotal;
            $sokratestotal->sokratestotal += $datum->sokratestotal;

            $electronicalnote->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $electronicalnote->data[]           = $datum->electronicalnote;
            $electronicalnote->electronicalnote += $datum->electronicalnote;

            $uploadmovie->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $uploadmovie->data[]      = $datum->uploadmovie;
            $uploadmovie->uploadmovie += $datum->uploadmovie;

            $production->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $production->data[]     = $datum->production;
            $production->production += $datum->production;

            $overturnclass->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $overturnclass->data[]        = $datum->overturnclass;
            $overturnclass->overturnclass += $datum->overturnclass;

            $learningprocess->time[] = $datum->targetYear . "-" . $datum->targetMonth;;
            $learningprocess->data[]          = 0;
            $learningprocess->learningprocess += $datum->HiTeach + $datum->onlinechecking + $datum->interclasscompetition + $datum->performancelogin + $datum->mergeactivity + $datum->onlinetest + $datum->analogytest;;
        }

        $schoolInfo->dashboard->smartclasstable->chartdata[] = $sokratestotal;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $electronicalnote;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $uploadmovie;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $production;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $overturnclass;
        $schoolInfo->dashboard->smartclasstable->chartdata[] = $learningprocess;

        //dashboard->smartclasstable->resource
        $schoolInfo->dashboard->smartclasstable->resource                       = (object)[];
        $schoolInfo->dashboard->smartclasstable->resource->subject              = 0;
        $schoolInfo->dashboard->smartclasstable->resource->examination          = 0;
        $schoolInfo->dashboard->smartclasstable->resource->textbook             = 0;
        $schoolInfo->dashboard->smartclasstable->resource->data                 = (object)[];
        $schoolInfo->dashboard->smartclasstable->resource->data->personagenum   = (object)['id' => 1, 'title' => '教师产生', 'num' => 0, 'time' => [], 'data' => []];
        $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum   = (object)['id' => 2, 'title' => '区级分享', 'num' => 0, 'time' => [], 'data' => []];
        $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum = (object)['id' => 3, 'title' => '校级分享', 'num' => 0, 'time' => [], 'data' => []];

        $select = '';
        $select = 'sum(resourceCount) as resourceCount,
                   sum(resourceSchSharedCount) as resourceSchSharedCount,
                   sum(resourceDisSharedCount) as resourceDisSharedCount,
                   sum(testPaperCount) as testPaperCount,
                   sum(testPaperSchSharedCount) as testPaperSchSharedCount,
                   sum(testPaperDisSharedCount) as testPaperDisSharedCount,
                   sum(testItemCount) as testItemCount,
                   sum(testItemSchSharedCount) as testItemSchSharedCount,
                   sum(testItemDisSharedCount) as testItemDisSharedCount,
                   targetYear,targetMonth';
        $data   = [];

        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->whereIn('m.SchoolID', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('m.SchoolID', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('teacher_data as t')
                    ->selectRaw($select)
                    ->join('member as m', 'm.MemberID', 't.member_id')
                    ->whereIn('m.SchoolID', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }

        $yearArray_index = 0;
        foreach ($data as $datum) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $schoolInfo->dashboard->smartclasstable->resource->data->personagenum->time[]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->dashboard->smartclasstable->resource->data->personagenum->data[]   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum->data[]   = 0;
                $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = 0;
                $yearArray_index++;
            }

            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }

            $schoolInfo->dashboard->smartclasstable->resource->subject                      += $datum->testItemCount;
            $schoolInfo->dashboard->smartclasstable->resource->examination                  += $datum->testPaperCount;
            $schoolInfo->dashboard->smartclasstable->resource->textbook                     += $datum->resourceCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->personagenum->time[]   = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->dashboard->smartclasstable->resource->data->personagenum->data[]   = $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->personagenum->num      += $datum->resourceCount + $datum->testPaperCount + $datum->testItemCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum->time[]   = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum->data[]   = $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->areasharenum->num      += $datum->resourceDisSharedCount + $datum->testPaperDisSharedCount + $datum->testItemDisSharedCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum->time[] = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum->data[] = $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
            $schoolInfo->dashboard->smartclasstable->resource->data->schoolsharenum->num    += $datum->resourceSchSharedCount + $datum->testPaperSchSharedCount + $datum->testItemSchSharedCount;
        }
        //study
        $schoolInfo->study->underway['percentage']   = 0;
        $schoolInfo->study->underway['id']           = 0;
        $schoolInfo->study->underway['title']        = '进行中';
        $schoolInfo->study->unfinished['percentage'] = 0;
        $schoolInfo->study->unfinished['id']         = 1;
        $schoolInfo->study->unfinished['title']      = '未完成';
        $schoolInfo->study->achieve['percentage']    = 0;
        $schoolInfo->study->achieve['id']            = 2;
        $schoolInfo->study->achieve['title']         = '已完成';

        $data   = [];
        $select = '';

        $select = 'sum(testingStuNum) as testingStuNum,
                   sum(testingStuCount) as testingStuCount,
                   sum(homeworkStuNum) as homeworkStuNum,
                   sum(homeworkStuCount) as homeworkStuCount,
                   sum(fcEventStuNum) as fcEventStuNum,
                   sum(fcEventStuCount) as fcEventStuCount,
                   sum(fcEventStuInProgress) as fcEventStuInProgress,
                   targetYear,targetMonth';
        switch ($semester) {
            case 0:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year)
                    ->whereBetween('targetMonth', [8, 12])
                    ->orWhere(function ($q) use ($query, $year) {
                        $q->whereIn('c.SchoolID', $query)
                            ->where('targetYear', $year + 1)
                            ->where('targetMonth', 1);
                    })
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
            case 1:
                $data = \DB::connection('middle')->table('course_data as d')
                    ->selectRaw($select)
                    ->join('course as c', 'c.CourseNO', 'd.course_no')
                    ->whereIn('c.SchoolID', $query)
                    ->where('targetYear', $year + 1)
                    ->whereBetween('targetMonth', [2, 7])
                    ->groupBy('targetYear', 'targetMonth')
                    ->get();
                break;
        }

        $stunum          = 0;
        $underway        = 0;
        $unfinished      = 0;
        $achieve         = 0;
        $testingnum      = 0;
        $testingcount    = 0;
        $homeworknum     = 0;
        $homeworkcount   = 0;
        $yearArray_index = 0;
        foreach ($data as $datum) {
            while (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) < ($datum->targetYear * 100 + $datum->targetMonth)) {
                $schoolInfo->study->underway['time'][]   = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->underway['data'][]   = 0;
                $schoolInfo->study->unfinished['time'][] = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->unfinished['data'][] = 0;
                $schoolInfo->study->achieve['time'][]    = $monthArray[$yearArray_index][0] . "-" . $monthArray[$yearArray_index][1];
                $schoolInfo->study->achieve['data'][]    = 0;
                $yearArray_index++;
            }
            if (($monthArray[$yearArray_index][0] * 100 + $monthArray[$yearArray_index][1]) == ($datum->targetYear * 100 + $datum->targetMonth)) {
                $yearArray_index++;
            }

            $schoolInfo->study->underway['time'][]   = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->underway['data'][]   = ($datum->fcEventStuNum) ? $datum->fcEventStuInProgress / $datum->fcEventStuNum * 100 : 0;
            $schoolInfo->study->unfinished['time'][] = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->unfinished['data'][] = ($datum->fcEventStuNum) ? $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress) / $datum->fcEventStuNum * 100 : 0;
            $schoolInfo->study->achieve['time'][]    = $datum->targetYear . "-" . $datum->targetMonth;
            $schoolInfo->study->achieve['data'][]    = ($datum->fcEventStuNum) ? $datum->fcEventStuCount / $datum->fcEventStuNum * 100 : 0;

            $stunum        += $datum->fcEventStuNum;
            $underway      += $datum->fcEventStuInProgress;
            $unfinished    += $datum->fcEventStuNum - ($datum->fcEventStuCount + $datum->fcEventStuInProgress);
            $achieve       += $datum->fcEventStuCount;
            $testingnum    += $datum->testingStuNum;
            $testingcount  += $datum->testingStuCount;
            $homeworknum   += $datum->homeworkStuNum;
            $homeworkcount += $datum->homeworkStuCount;
        }
        $schoolInfo->study->underway['percentage']   = ($stunum) ? ($underway / $stunum) * 100 : 0;
        $schoolInfo->study->unfinished['percentage'] = ($stunum) ? ($unfinished / $stunum) * 100 : 0;
        $schoolInfo->study->achieve['percentage']    = ($stunum) ? ($achieve / $stunum) * 100 : 0;
        $schoolInfo->study->onlinetestcomplete       = ($testingnum) ? ($testingcount / $testingnum) * 100 : 0;
        $schoolInfo->study->productionpercentage     = ($homeworknum) ? ($homeworkcount / $homeworknum) * 100 : 0;


        return $schoolInfo;
    }

}