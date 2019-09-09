<?php /** @noinspection ALL */

namespace App\Http\Controllers\Admin\Api;

use App\Models\Api_a1_log;
use App\Models\CmsResource;
use App\Models\Course;
use App\Models\Exercise;
use App\Models\Exscore;
use App\Models\Fc_event;
use App\Models\Hiteach_log;
use App\Models\Iteminfo;
use App\Models\Member;
use App\Models\SchoolInfo;
use App\Models\Testpaper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// 設定給跨網域api 使用
header('Access-Control-Allow-Origin:*');
// 设置允许的响应类型
header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
// 设置允许的响应头
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class DataController extends Controller
{

    public function getAll(Request $request)
    {

        //預設資訊全部
        //預設時間
        // $StartTime = DB::table('member')->selectRaw('max(date(RegisterTime)) date')->whereRaw('year(RegisterTime) >= 0')->get(); //開始時間
        // $EndTime  =  DB::table('member')->selectRaw('min(date(RegisterTime)) date')->whereRaw('year(RegisterTime) >= 0')->get();   //結束時間
        $schoolID = $request->SchoolID;
        // $memberID  = $request->MemberID;

        if ($request->StartTime && $request->EndTime && ($schoolID === 0) ? true : $schoolID == true) {
            // dd('1');
            $StartTime = $request->StartTime;   //開始時間
            $EndTime   = $request->EndTime;     //結束時間
            $schoolID  = $request->SchoolID;    //學校代號

            return $this->getSchoolJson($StartTime, $EndTime, $schoolID);

        } elseif ($request->StartTime && $request->EndTime == true) {
            // dd('2');
            $StartTime = $request->StartTime;   //開始時間
            $EndTime   = $request->EndTime;     //結束時間

            return $this->getDate($StartTime, $EndTime);

        } elseif (isset($schoolID)) {
            // dd('3');
            $schoolID = $request->SchoolID;    //學校代號

            return $this->getSchooIDNotDateJson($schoolID);

        } elseif ($request->MemberID && $request->StartTime && $request->EndTime == true) {
            // dd('4');
            $StartTime = $request->StartTime;   //開始時間
            $EndTime   = $request->EndTime;     //結束時間
            $memberID  = $request->MemberID;    //學校代號
            //尚無資訊
            return $this->teacher();

        } elseif (isset($request->MemberID)) {
            // dd('5');
            $memberID = $request->MemberID;    //學校代號
            //尚無資訊
            return $this->teacher();

        } else {
            // dd('6');
            return $this->all();
        }
    }

    //全部學校總數
    private function getSchoolAll($StartTime, $EndTime)
    {

        //學校名稱
        $schoolname = '所有學校';

        //學校ID
        $schoolid = 'All';

        // 老師啟用數
        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->count();

        //學生啟用數
        $studentnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'S');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->count();

        //家長啟用數
        $patriarchnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'P');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->count();

        // 老師登入數
        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('IDLevel', 'T')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();

        foreach ($teachlogintables as $item) {
            $teachlogintableTime[] = $item->year;
            $teachlogintableData[] = $item->total;
        }


        //課程總數
        $curriculum = Course::query()->select('SchoolID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->count();

        //老師上傳影片
        $uploadmovie =
            CmsResource::query()->select('member_id', 'created_dt')->whereHas('Member', function ($memberQuery) {

                $memberQuery->whereHas('systemauthority', function ($query) {
                    $query->where('IDLevel', 'T');
                });
            })
                ->whereBetween('created_dt', [$StartTime, $EndTime])
                ->count();

        //作業作品數 (需要再確認)
        $production = Course::query()->select('ConurseNO')->whereHas('Teachomework', function ($queruy) {
            $queruy->select('ClassID');
        })
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->count();

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->count();

        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')->whereHas('Course', function ($query) {

        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdata = $this->chartdatasList($StartTime, $EndTime);

        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) {

                    });
                });
            })
            ->whereBetween('Date', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();

        foreach ($subjectnums as $item) {
            $subjectnumData[] = $item["total"];
            $subjectnumTime[] = $item["year"];
        }

        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) {

            })
            ->whereBetween('CreateTime', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        foreach ($examinationnums as $item) {
            $examinationnumData[] = $item["total"];
            $examinationnumTime[] = $item["year"];
        }

        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();

        //進行中 to object
        foreach ($underways as $item) {
            $underwayData[] = $item->total;
            $underwayTime[] = $item->year;
        }

        //未完成
        $unfinished = DB::table('fc_sub_event')
            ->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        foreach ($unfinished as $item) {
            $unfinishedData[] = $item->total;
            $unfinishedTime[] = $item->year;
        }

        //完成
        $achieves = DB::table('fc_sub_event')
            ->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();

        foreach ($achieves as $item) {
            $achieveData[] = $item->total;
            $achieveTime[] = $item->year;
        }
        // 全部學校
        // $data = $this->getList($StartTime, $EndTime);


        $data = [
            'schoolname'           => $schoolname,                              //學校名稱
            'schoolid'             => $schoolid,                                //學校ID，所有為all，學校為數字ID
            'teachnum'             => $teachnum,                                //老師帳號啟用數量
            'studentnum'           => $studentnum,                              //學生帳號啟用數量
            'patriarchnum'         => $patriarchnum,                            //家長帳號啟用數量
            'dashboard'            => [
                'teachlogintable' => [                                          //老師登入
                    'time' => $teachlogintableTime,
                    'data' => $teachlogintableData,
                ],
                'smartclasstable' => [
                    'curriculum'         => $curriculum,                           //課程總數
                    'uploadmovie'        => $uploadmovie,                          //上傳影片
                    'production'         => $production,                           //作業作品數
                    'overturnclass'      => $overturnclass,                        //翻轉課堂
                    'learningprocess'    => [
                        'analogytest'           => $analogytest,                //模擬測驗
                        'onlinetest'            => $onlinetest,                 //線上測驗
                        'interclasscompetition' => $interclasscompetition,      //班級競賽
                        'HiTeach'               => $HiTeach,                    //HiTeach
                        'performancelogin'      => $performancelogin,           //成績登入
                        'mergeactivity'         => $mergeactivity,              //合並活動
                        'onlinechecking'        => $onlinechecking,             //網路閱卷
                        "alllearningprocess"    => $alllearningprocess,         //學習歷程總數
                    ],
                    "chartdatachartdata" => $chartdata,                   ////智慧教堂應用下２個圖表
                    "resource"           => [
                        "personage"        => null,                          //个人所属
                        "areashare"        => null,                          //区级分享
                        "schoolshare"      => null,                          //校级分享
                        "overallresourece" => null,                          //总资源分享数
                        "subjectnum"       => [                              //題目數
                            'id'   => $schoolid,
                            "time" => $subjectnumTime,
                            "data" => $subjectnumData,
                        ],
                        "examinationnum"   => [                                    //試卷數
                            'id'   => $schoolid,
                            "time" => $examinationnumTime,
                            "data" => $examinationnumData,
                        ],
                    ],
                ],
            ],
            "textbooknum"          => [ //教材数 { "2null12": 457, "2013": 865, 目前沒有教材數
                "time" => null,
                "data" => null,
            ],
            "study"                => [
                "underway"     => [         //进行中
                    "percentage" => null,
                    "time"       => $underwayTime,
                    "data"       => $underwayData,

                ],
                "unfinished"   => [          //未完成
                    // "percentage" => null,
                    // "data"       => $unfinishedData,
                    // "time"       => $unfinishedTime,

                ],
                "achieve"      => [         //已完成
                    "percentage" => null,
                    "time"       => $achieveTime,
                    "data"       => $achieveData,
                ],
                "selfstudynum" => [    //自学任务总数
                    "percentage" => null,
                    "time"       => null,
                    "data"       => null,

                ]
            ],
            "onlinetestcomplete"   => $onlinetestcomplete,                          //線上測驗完成率
            "productionpercentage" => $productionpercentage,                        //作業作品完成率
        ];

        return $data;
    }


    private function getNotDateList()
    {

        //學校名稱
        $schoolname = '所有學校';

        //學校ID
        $schoolid = 'All';

        // 老師啟用數
        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })
            ->select('MemberID', 'Status')
            ->where('Status', '1')
            ->count();

        //學生啟用數
        $studentnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'S');
        })
            ->select('MemberID', 'Status')
            ->where('Status', '1')
            ->count();

        //家長啟用數
        $patriarchnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'P');
        })
            ->select('MemberID', 'Status')
            ->where('Status', '1')
            ->count();

        // 老師登入數
        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->where('IDLevel', 'T')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();

        foreach ($teachlogintables as $item) {
            $teachlogintableTime[] = $item->year;
            $teachlogintableData[] = $item->total;
        }


        //課程總數
        $curriculum = Course::query()->select('SchoolID')
            ->count();

        //老師上傳影片
        $uploadmovie =
            CmsResource::query()->select('member_id', 'created_dt')->whereHas('Member', function ($memberQuery) {

                $memberQuery->whereHas('systemauthority', function ($query) {
                    $query->where('IDLevel', 'T');
                });
            })
                ->count();

        //作業作品數 (需要再確認)
        $production = Course::query()->select('ConurseNO')->whereHas('Teachomework', function ($queruy) {
            $queruy->select('ClassID');
        })
            ->count();

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->count();

        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')->whereHas('Course', function ($query) {

        })
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')->whereHas('Course', function ($query) {

        })
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdata = $this->chartdatasNotDate();

        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) {

                    });
                });
            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();

        foreach ($subjectnums as $item) {
            $subjectnumData[] = $item["total"];
            $subjectnumTime[] = $item["year"];
        }

        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) {

            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        foreach ($examinationnums as $item) {
            $examinationnumData[] = $item["total"];
            $examinationnumTime[] = $item["year"];
        }

        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();

        //進行中 to object
        foreach ($underways as $item) {
            $underwayData[] = $item->total;
            $underwayTime[] = $item->year;
        }

        //未完成
        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        foreach ($unfinished as $item) {
            $unfinishedData[] = $item->total;
            $unfinishedTime[] = $item->year;
        }

        //完成
        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();

        foreach ($achieves as $item) {
            $achieveData[] = $item->total;
            $achieveTime[] = $item->year;
        }
        // 全部學校
        // $data = $this->getList($StartTime, $EndTime);


        $data = [
            'schoolname'           => $schoolname,                              //學校名稱
            'schoolid'             => $schoolid,                                //學校ID，所有為all，學校為數字ID
            'teachnum'             => $teachnum,                                //老師帳號啟用數量
            'studentnum'           => $studentnum,                              //學生帳號啟用數量
            'patriarchnum'         => $patriarchnum,                            //家長帳號啟用數量
            'dashboard'            => [
                'teachlogintable' => [                                          //老師登入
                    'time' => $teachlogintableTime,
                    'data' => $teachlogintableData,
                ],
                'smartclasstable' => [
                    'curriculum'         => $curriculum,                        //課程總數
                    'uploadmovie'        => $uploadmovie,                       //上傳影片
                    'production'         => $production,                        //作業作品數
                    'overturnclass'      => $overturnclass,                     //翻轉課堂
                    'learningprocess'    => [
                        'analogytest'           => $analogytest,                //模擬測驗
                        'onlinetest'            => $onlinetest,                 //線上測驗
                        'interclasscompetition' => $interclasscompetition,      //班級競賽
                        'HiTeach'               => $HiTeach,                    //HiTeach
                        'performancelogin'      => $performancelogin,           //成績登入
                        'mergeactivity'         => $mergeactivity,              //合並活動
                        'onlinechecking'        => $onlinechecking,             //網路閱卷
                        "alllearningprocess"    => $alllearningprocess,         //學習歷程總數
                    ],
                    "chartdatachartdata" => $chartdata,                   ////智慧教堂應用下２個圖表
                    "resource"           => [
                        "personage"        => null,                        //个人所属
                        "areashare"        => null,                         //区级分享
                        "schoolshare"      => null,                         //校级分享
                        "overallresourece" => null,                        //总资源分享数
                        "subjectnum"       => [                                   //題目數
                            'id'   => $schoolid,
                            "time" => $subjectnumTime,
                            "data" => $subjectnumData,
                        ],
                        "examinationnum"   => [                                    //試卷數
                            'id'   => $schoolid,
                            "time" => $examinationnumTime,
                            "data" => $examinationnumData,
                        ],
                    ],
                ],
            ],
            "textbooknum"          => [ //教材数 { "2null12": 457, "2013": 865, 目前沒有教材數
                "time" => null,
                "data" => null,
            ],
            "study"                => [
                "underway"     => [         //进行中
                    "percentage" => null,
                    "time"       => $underwayTime,
                    "data"       => $underwayData,

                ],
                "unfinished"   => [          //未完成
                    // "percentage" => null,
                    // "data"       => $unfinishedData,
                    // "time"       => $unfinishedTime,

                ],
                "achieve"      => [         //已完成
                    "percentage" => null,
                    "time"       => $achieveTime,
                    "data"       => $achieveData,
                ],
                "selfstudynum" => [    //自学任务总数
                    "percentage" => null,
                    "time"       => null,
                    "data"       => null,

                ]
            ],
            "onlinetestcomplete"   => $onlinetestcomplete,                          //線上測驗完成率
            "productionpercentage" => $productionpercentage,                        //作業作品完成率
        ];

        return $data;
    }

    //智慧教堂應用下２個圖表
    private function chartdatasList($StartTime, $EndTime)
    {

        // $StartTime = '2011-01-10';
        // $EndTime   = '2018-01-10';
        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($schools as $school) {
            //智慧教堂應用下２個圖表
            $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
                ->whereHas('Course', function ($query) use ($school) {
                    $query->where('SchoolID', $school->SchoolID);
                })
                ->whereBetween('ExTime', [$StartTime, $EndTime])
                ->where('ExType', '!=', 'K')
                ->Where('Rule', 'not like', '%K%')
                ->groupBy(DB::raw('year(ExTime)'))
                ->get();

            if (count($chartdatas) != 0) {
                foreach ($chartdatas as $item) {
                    if (count($item) != 0) {
                        $chartTime[] = $item['year'];
                        $chartData[] = $item['total'];
                    }
                }
                $time[]    = [
                    'id'   => $school->SchoolID,
                    'time' => $chartTime,                                   //智慧教堂應用下２個圖表
                    'data' => $chartData,                                   //智慧教堂應用下２個圖表
                ];
                $chartData = [];
                $chartTime = [];
            } else {
                $time = [
                    'id'   => null,
                    'time' => null,                                   //智慧教堂應用下２個圖表
                    'data' => null,                                   //智慧教堂應用下２個圖表
                ];
                return $time;
            }
        }
        return $time;
    }

    //智慧教堂應用下２個圖表
    private function chartdatasNotDate()
    {
        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($schools as $school) {
            //智慧教堂應用下２個圖表
            $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
                ->whereHas('Course', function ($query) use ($school) {
                    $query->where('SchoolID', $school->SchoolID);
                })
                ->where('ExType', '!=', 'K')
                ->Where('Rule', 'not like', '%K%')
                ->groupBy(DB::raw('year(ExTime)'))
                ->get();

            foreach ($chartdatas as $item) {
                $chartData[] = $item['total'];
                $chartTime[] = $item['year'];

            }

            $time[] = [
                'id'   => $school->SchoolID,
                "time" => $chartTime,                                   //智慧教堂應用下２個圖表
                "data" => $chartData,                                   //智慧教堂應用下２個圖表
            ];

            $chartData = [];
            $chartTime = [];
        }
        return $time;
    }

    //  取得學校資訊
    private function getSchool($StartTime, $EndTime, $schoolID)
    {
        // $StartTime = $request->StartTime;   //開始時間
        // $EndTime   = $request->EndTime;     //結束時間
        // $schoolID  = $request->SchoolID;      //學校代號

        //學校名稱
        $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID',
            $schoolID)->value('SchoolName');

        //學校ID
        $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID',
            $schoolID)->value('SchoolID');

        // 老師啟用數
        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->where('SchoolID', $schoolID)
            ->count();

        //學生啟用數
        $studentnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'S');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->where('SchoolID', $schoolID)
            ->count();

        //家長啟用數
        $patriarchnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'P');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->where('SchoolID', $schoolID)
            ->count();

        // 老師登入數
        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('IDLevel', 'T')
            ->where('SchoolID', $schoolID)
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();

        if (!$teachlogintables->toArray() == null) {
            foreach ($teachlogintables as $item) {
                $teachlogintableTime[] = $item->year;
                $teachlogintableData[] = $item->total;
            }
        }
        $teachlogintableTime = 0;
        $teachlogintableData = 0;

        //學生登入數
        $studyuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'S')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $studyuserusingTotal[] = $item->total;
                $studyuserusingYear[]  = $item->year;
            }
        } else {
            $studyuserusingTotal = 0;
            $studyuserusingYear  = 0;
        }

        //家長登入數
        $patriarchuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'P')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $patriarchuserusingTotal[] = $item->total;
                $patriarchuserusingYear[]  = $item->year;
            }
        } else {
            $patriarchuserusingTotal = 0;
            $patriarchuserusingYear  = 0;
        }
        //課程總數
        $curriculum = Course::query()->select('SchoolID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->count();

        //老師上傳影片
        $uploadmovie =
            CmsResource::query()->select('member_id', 'created_dt')->whereHas('Member', function ($memberQuery) use ($schoolID) {
                $memberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                $memberQuery->whereHas('systemauthority', function ($query) {
                    $query->where('IDLevel', 'T');
                });
            })
                ->whereBetween('created_dt', [$StartTime, $EndTime])
                ->count();

        //作業作品數 (需要再確認)
        $production = Course::query()->select('ConurseNO')->whereHas('Teachomework', function ($queruy) {
            $queruy->select('ClassID');
        })
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->count();

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->where('SchoolID', $schoolID)
            ->count();

        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))->whereHas('Course', function ($query) use ($schoolID) {
            $query->where('SchoolID', $schoolID);
        })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->groupBy(DB::raw('year(ExTime)'))
            ->get();
        if (!$chartdatas->toArray() == null) {
            foreach ($chartdatas as $item) {
                $chartData[] = $item['total'];
                $chartTime[] = $item['year'];
            }
        }
        $chartData = 0;
        $chartTime = 0;


        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) use ($schoolID) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($schoolID) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($schoolID) {
                        $MemberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                    });
                });
            })
            ->whereBetween('Date', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();
        if (!$subjectnums->toArray() == null) {
            foreach ($subjectnums as $item) {
                $subjectnumData[] = $item["total"];
                $subjectnumTime[] = $item["year"];
            }
        }
        $subjectnumData = 0;
        $subjectnumTime = 0;


        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) use ($schoolID) {
                $query->select('SchoolID')->where('SchoolID', $schoolID);
            })
            ->whereBetween('CreateTime', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        if (!$examinationnums->toArray() == null) {
            foreach ($examinationnums as $item) {
                $examinationnumData[] = $item["total"];
                $examinationnumTime[] = $item["year"];
            }
        }
        $examinationnumData = 0;
        $examinationnumTime = 0;

        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('SchoolID', $schoolID)->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$underways->toArray() == null) {
            //進行中 to object
            foreach ($underways as $item) {
                $underwayData[] = $item->total;
                $underwayTime[] = $item->year;
            }
        }
        $underwayData = 0;
        $underwayTime = 0;


        //未完成
        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$unfinished->toArray() == null) {
            foreach ($unfinished as $item) {
                $unfinishedData[] = $item->total;
                $unfinishedTime[] = $item->year;
            }
        }
        $unfinishedData = 0;
        $unfinishedTime = 0;


        //完成
        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$achieves->toArray() == null) {
            foreach ($achieves as $item) {
                $achieveData[] = $item->total;
                $achieveTime[] = $item->year;
            }
        }
        $achieveData = 0;
        $achieveTime = 0;


        $data[] = [
            'schoolname'           => $schoolname,                              //學校名稱
            'schoolid'             => $schoolID,                                //學校ID，所有為all，學校為數字ID
            'teachnum'             => $teachnum,                                //老師帳號啟用數量
            'studentnum'           => $studentnum,                              //學生帳號啟用數量
            'patriarchnum'         => $patriarchnum,                            //家長帳號啟用數量
            'dashboard'            => [
                'teachlogintable' => [                                          //老師登入
                    'time' => $teachlogintableTime,
                    'data' => $teachlogintableData,
                ],
                'smartclasstable' => [
                    'curriculum'      => $curriculum,                           //課程總數
                    'uploadmovie'     => $uploadmovie,                          //上傳影片
                    'production'      => $production,                           //作業作品數
                    'overturnclass'   => $overturnclass,                        //翻轉課堂
                    'learningprocess' => [
                        'analogytest'           => $analogytest,                //模擬測驗
                        'onlinetest'            => $onlinetest,                 //線上測驗
                        'interclasscompetition' => $interclasscompetition,      //班級競賽
                        'HiTeach'               => $HiTeach,                    //HiTeach
                        'performancelogin'      => $performancelogin,           //成績登入
                        'mergeactivity'         => $mergeactivity,              //合並活動
                        'onlinechecking'        => $onlinechecking,             //網路閱卷
                        "alllearningprocess"    => $alllearningprocess,         //學習歷程總數
                    ],
                    "chartdata"       => [
                        'id'   => $schoolID,
                        "time" => $chartTime,                                   //智慧教堂應用下２個圖表
                        "data" => $chartData,                                   //智慧教堂應用下２個圖表
                    ],
                    "resource"        => [
                        "personage"        => null,                               //个人所属
                        "areashare"        => null,                               //区级分享
                        "schoolshare"      => null,                               //校级分享
                        "overallresourece" => null,                               //总资源分享数
                        "subjectnum"       => [                                   //題目數
                            'id'   => $schoolID,
                            "time" => $subjectnumTime,
                            "data" => $subjectnumData,
                        ],
                        "examinationnum"   => [                                    //試卷數
                            'id'   => $schoolID,
                            "time" => $examinationnumTime,
                            "data" => $examinationnumData,
                        ],
                    ],
                ],
            ],
            "textbooknum"          => [ //教材数 { "2012": 457, "2013": 865, 目前沒有教材數
                "2014" => null,
                "2015" => null,
                "2016" => null,
                "2017" => null,
                "2018" => null,
            ],
            "study"                => [
                "underway"     => [         //进行中
                    "percentage" => null,
                    "time"       => $underwayTime,
                    "data"       => $underwayData,

                ],
                "unfinished"   => [          //未完成
                    "percentage" => null,
                    "time"       => null,
                    "data"       => null,

                ],
                "achieve"      => [         //已完成
                    "percentage" => null,
                    "time"       => $achieveTime,
                    "data"       => $achieveData,
                ],
                "selfstudynum" => [    //自学任务总数
                    "percentage" => null,
                    "time"       => null,
                    "data"       => null,
                ]
            ],
            "onlinetestcomplete"   => $onlinetestcomplete,                          //線上測驗完成率
            "productionpercentage" => $productionpercentage,                        //作業作品完成率
        ];
        //初始化
        $teachlogintableTime = [];
        $teachlogintableData = [];
        $chartTime           = [];
        $chartData           = [];
        $achieveTime         = [];
        $achieveData         = [];
        $underwayTime        = [];
        $underwayData        = [];
        $subjectnumData      = [];
        $subjectnumTime      = [];
        $examinationnumData  = [];
        $examinationnumTime  = [];
        $unfinishedTime      = [];
        $unfinishedData      = [];
        // }
        return response()->json($data, 200);
    }


    //不篩選學校ＩＤ
    // private function getDate($StartTime, $EndTime)
    public function getDate()
    {
        // 起始時間年份
        // 起始時間
        $start = DB::table('member')->selectRaw('year(RegisterTime) year')->groupBy(DB::raw('year(RegisterTime)'))->get();
        // 當下時間
        $now = Carbon::now()->format('Y-m-d');
        /*foreach ($start as $item) {
            if ($item->year <= $now) {
                if (isset($item->year)){
                    $StartTime[] = "$item->year-08-01";
                    $StartTime[] = "$item->year-02-01";
                    $EndTime[]   = $item->year + 1 . '-01-31';
                    $EndTime[]   = $item->year + 1 . '-07-31';
                }
            }
        }*/


        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($schools as $school) {
            foreach ($start as $item) {
                if ($item->year <= $now) {
                    if (isset($item->year)) {
                        // $StartTime[] = $item->year . '-08-01';
                        $StartTime[] = $item->year . '-02-01';
                        // $EndTime[] = $item->year + 1 . '-01-31';
                        $EndTime[] = $item->year + 1 . '-07-31';
                        $year      = $item->year;

                        //學校名稱
                        $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID', $school->SchoolID)->value('SchoolName');

                        //學校ID
                        $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID', $school->SchoolID)->value('SchoolID');

                        // 老師啟用數
                        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
                            $query->select('IDLevel')->where('IDLevel', 'T');
                        })
                            ->select('MemberID', 'Status')
                            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                            ->where('Status', '1')
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        //學生啟用數
                        $studentnum = Member::query()->whereHas('Systemauthority', function ($query) {
                            $query->select('IDLevel')->where('IDLevel', 'S');
                        })
                            ->select('MemberID', 'Status')
                            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                            ->where('Status', '1')
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        //家長啟用數
                        $patriarchnum = Member::query()->whereHas('Systemauthority', function ($query) {
                            $query->select('IDLevel')->where('IDLevel', 'P');
                        })
                            ->select('MemberID', 'Status')
                            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                            ->where('Status', '1')
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        // 老師登入數
                        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                            ->where('IDLevel', 'T')
                            ->where('SchoolID', $school->SchoolID)
                            ->groupBy(DB::raw('year(RegisterTime)'))
                            ->get();


                        //課程總數
                        $curriculum = Course::query()->select('SchoolID')
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        //老師上傳影片
                        $uploadmovie =
                            CmsResource::query()->select('member_id', 'created_dt')->whereHas('Member', function ($memberQuery) use ($school) {
                                $memberQuery->select('SchoolID')->where('SchoolID', $school->SchoolID);
                                $memberQuery->whereHas('systemauthority', function ($query) {
                                    $query->where('IDLevel', 'T');
                                });
                            })
                                ->whereBetween('created_dt', [$StartTime, $EndTime])
                                ->count();

                        //作業作品數 (需要再確認)
                        $production = Course::query()->select('ConurseNO')->whereHas('Teachomework', function ($queruy) {
                            $queruy->select('ClassID');
                        })
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        //翻轉課堂數
                        $overturnclass = DB::table('course')
                            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('public_flag', '1')
                            ->where('active_flag', '1')
                            ->where('status', '!=', 'D')
                            ->where('SchoolID', $school->SchoolID)
                            ->count();

                        //模擬測驗 I
                        $analogytest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'I')
                            ->count();

                        //線上測驗 A
                        $onlinetest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'A')
                            ->count();

                        //班級競賽 J && K
                        $interclasscompetition = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'J')
                            ->orWhere('ExType', 'K')
                            ->count();

                        //HiTeach   H
                        $HiTeach = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'H')
                            ->count();

                        //成績登陸    S
                        $performancelogin = Exercise::query()->select('ExType')->whereHas('Course',
                            function ($query) use ($school) {
                                $query->where('SchoolID', $school->SchoolID);
                            })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'S')
                            ->count();

                        //合併活動 L
                        $mergeactivity = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'L')
                            ->count();

                        //網路閱卷 O
                        $onlinechecking = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', 'O')
                            ->count();

                        //學習歷程總數 EXtype != K  rule not like %k
                        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')->whereHas('Course', function ($query) use ($school) {
                            $query->where('SchoolID', $school->SchoolID);
                        })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', '!=', 'K')
                            ->Where('Rule', 'not like', '%K%')
                            ->count();

                        //智慧教堂應用下２個圖表
                        $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
                            ->whereHas('Course', function ($query) use ($school) {
                                $query->where('SchoolID', $school->SchoolID);
                            })
                            ->whereBetween('ExTime', [$StartTime, $EndTime])
                            ->where('ExType', '!=', 'K')
                            ->Where('Rule', 'not like', '%K%')
                            ->groupBy(DB::raw('year(ExTime)'))
                            ->get();

                        //题目數
                        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
                            ->whereHas('Testitem', function ($TestitemQuery) use ($school) {
                                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($school) {
                                    $TestpaperQuery->select('Status')->where('Status', 'E');
                                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($school) {
                                        $MemberQuery->select('SchoolID')->where('SchoolID', $school->SchoolID);
                                    });
                                });
                            })
                            ->whereBetween('Date', [$StartTime, $EndTime])
                            ->where('Status', 'E')
                            ->groupBy(DB::raw('year(Date)'))
                            ->get();

                        // foreach ($subjectnums as $item) {
                        //     $subjectnumData[] = $item["total"];
                        //     $subjectnumTime[] = $item["year"];
                        // }

                        //試卷數
                        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                            ->whereHas('Member', function ($query) use ($school) {
                                $query->select('SchoolID')->where('SchoolID', $school->SchoolID);
                            })
                            ->whereBetween('CreateTime', [$StartTime, $EndTime])
                            ->where('Status', 'E')
                            ->groupBy(DB::raw('year(CreateTime)'))
                            ->get();

                        // foreach ($examinationnums as $item) {
                        //     $examinationnumData[] = $item["total"];
                        //     $examinationnumTime[] = $item["year"];
                        // }

                        //線上測驗完成率
                        //分子 有做過作業的人
                        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('ExType', 'A')
                            ->where('Rule', 'not like', '%K%')
                            ->where('exscore.AnsNum', '>', '0')
                            ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
                        //分母 全部但不一定有做過作業
                        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('ExType', 'A')
                            ->where('Rule', 'not like', '%K%')
                            ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

                        //線上測驗完成率
                        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

                        //完成作業的人數
                        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
                            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                            ->where('ExType', 'A')
                            ->where('Rule', 'not like', '%K%')
                            ->where('exscore.AnsNum', '>', '0')
                            ->where('SchoolID', $school->SchoolID)->count();
                        //作業作品完成率 做成作業的人/全部不一定做過作業的人
                        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

                        //進行中
                        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                            ->whereBetween('create_time', [$StartTime, $EndTime])
                            ->where('SchoolID', $school->SchoolID)
                            ->where('fc_event.status', '!=', 'D')
                            ->where('fc_event.active_flag', 1)
                            ->where('fc_event.public_flag', 1)
                            ->where('fc_target.active_flag', 1)
                            ->groupBy(DB::raw('year(create_time)'))
                            ->get();

                        //進行中 to object
                        // foreach ($underways as $item) {
                        //     $underwayData[] = $item->total;
                        //     $underwayTime[] = $item->year;
                        // }

                        //未完成
                        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                            ->whereBetween('create_time', [$StartTime, $EndTime])
                            ->where('SchoolID', $school->SchoolID)
                            ->where('fc_event.status', '!=', 'D')
                            ->where('fc_event.active_flag', 0)
                            ->where('fc_event.public_flag', 0)
                            ->groupBy(DB::raw('year(create_time)'))
                            ->get();
                        // foreach ($unfinished as $item) {
                        //
                        //     $unfinishedData[] = $item->total;
                        //     $unfinishedTime[] = $item->year;
                        // }
                        //完成
                        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                            ->whereBetween('create_time', [$StartTime, $EndTime])
                            ->where('SchoolID', $school->SchoolID)
                            ->where('fc_event.status', '!=', 'D')
                            ->where('fc_event.active_flag', 1)
                            ->where('fc_event.public_flag', 1)
                            ->groupBy(DB::raw('year(create_time)'))
                            ->get();

                        // foreach ($achieves as $item) {
                        //     $achieveData[] = $item->total;
                        //     $achieveTime[] = $item->year;
                        // }
                        // 全部學校
                        // $data[0] = $this->getSchoolAll($StartTime, $EndTime);


                        $data[] = [
                            'schoolname'   => $schoolname,                              //學校名稱
                            'schoolid'     => $schoolid,                                //學校ID，所有為all，學校為數字ID
                            'teachnum'     => $teachnum,                                //老師帳號啟用數量
                            'studentnum'   => $studentnum,                              //學生帳號啟用數量
                            'patriarchnum' => $patriarchnum,                            //家長帳號啟用數量
                            'dashboard'    => [
                                'teachlogintable' => $teachlogintables,                                         //老師登入
                                'smartclasstable' => [
                                    'curriculum'      => $curriculum,                           //課程總數
                                    'uploadmovie'     => $uploadmovie,                          //上傳影片
                                    'production'      => $production,                           //作業作品數
                                    'overturnclass'   => $overturnclass,                        //翻轉課堂
                                    'learningprocess' => [
                                        'analogytest'           => $analogytest,                //模擬測驗
                                        'onlinetest'            => $onlinetest,                 //線上測驗
                                        'interclasscompetition' => $interclasscompetition,      //班級競賽
                                        'HiTeach'               => $HiTeach,                    //HiTeach
                                        'performancelogin'      => $performancelogin,           //成績登入
                                        'mergeactivity'         => $mergeactivity,              //合並活動
                                        'onlinechecking'        => $onlinechecking,             //網路閱卷
                                        "alllearningprocess"    => $alllearningprocess,         //學習歷程總數
                                    ],
                                    "chartdata"       => $chartdatas,                          //智慧教堂應用下２個圖表
                                    "resource"        => [
                                        "personage"        => null,                        //个人所属
                                        "areashare"        => null,                         //区级分享
                                        "schoolshare"      => null,                         //校级分享
                                        "overallresourece" => null,                        //总资源分享数
                                        "subjectnum"       => $subjectnums,                                    //題目數
                                        "examinationnum"   => $examinationnums,                                //試卷數
                                    ],
                                ],
                            ],
                            "textbooknum"  => null,    //教材数 { "2012": 457, "2013": 865, 目前沒有教材數
                            "study"        => [
                                "underway"             => $underways,//进行中
                                // "percentage" => null,
                                "unfinished"           => $unfinished,//未完成
                                // "percentage"           => null,
                                "achieve"              => $achieves,    //已完成
                                // "percentage"           => null,
                                "selfstudynum"         => null,    //自学任务总数
                                // "percentage"           => null,
                                "onlinetestcomplete"   => $onlinetestcomplete,                          //線上測驗完成率
                                "productionpercentage" => $productionpercentage,                        //作業作品完成率
                            ],
                        ];
                        // var_dump($data[0]);
                        // dd($data);

                        DB::table('ApiDistricts')->insert([
                            'schoolName'            => $schoolname,
                            'schoolId'              => $schoolid,
                            'teachnum'              => $teachnum,
                            'studentnum'            => $studentnum,
                            'patriarchnum'          => $patriarchnum,
                            'teachlogintable'       => $teachlogintables,
                            // 'teachlogintableData'    => $teachlogintableData,
                            'curriculum'            => $curriculum,
                            'uploadMovie'           => $uploadmovie,
                            'production'            => $production,
                            'overturnClass'         => $overturnclass,
                            'analogyTest'           => $analogytest,
                            'onlineTest'            => $onlinetest,
                            'interClassCompetition' => $interclasscompetition,
                            'HiTeach'               => $HiTeach,
                            'performanceLogin'      => $performancelogin,
                            'mergeActivity'         => $mergeactivity,
                            'onlineChecking'        => $onlinechecking,
                            'allLearningProcess'    => $alllearningprocess,
                            'chartdata'             => $chartdatas,
                            'personAge'             => null,
                            'areaShare'             => null,
                            'schoolShare'           => null,
                            'overallResourece'      => null,
                            'subjectnum'            => $subjectnums,
                            // 'subjectnumTime'         => $subjectnumTime,
                            // 'subjectnumData'         => $subjectnumData,
                            'examinationnum'        => $examinationnums,
                            // 'examinationnumTime'     => $examinationnumTime,
                            // 'examinationnumData'     => $examinationnumData,
                            'textbooknum'           => null,
                            // 'textbooknumData'        => null,
                            // 'underwayPercentage'     => null,
                            'underway'              => $underways,
                            // 'underwayData'           => $underwayData,
                            // 'achievePercentage'      => null,
                            'achieve'               => $achieves,
                            // 'achieveData'            => $achieveData,
                            // 'selfstudynumPercentage' => null,
                            'selfstuDynum'          => null,
                            // 'selfstuDynumData'       => null,
                            'onlineTestComplete'    => $onlinetestcomplete,
                            'productionPercentage'  => $productionpercentage,
                            'semester'              => 1,
                            'year'                  => $year,
                        ]);
                        //初始化
                        $StartTime = [];
                        $EndTime   = [];
                        // // $teachlogintableTime = [];
                        // $teachlogintableData = [];
                        // // $chartTime           = [];
                        // // $chartData           = [];
                        // $achieveTime        = [];
                        // $achieveData        = [];
                        // $underwayTime       = [];
                        // $underwayData       = [];
                        // $subjectnumData     = [];
                        // $subjectnumTime     = [];
                        // $examinationnumData = [];
                        // $examinationnumTime = [];
                        // $unfinishedTime     = [];
                        // $unfinishedData     = [];

                    }
                }
            }
            ini_set('memory_limit', '-1');
        }


        return response()->json($data, 200);
    }

    //預設全部
    public function all()
    {
        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($schools as $school) {
            //學校名稱
            $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID',
                $school->SchoolID)->value('SchoolName');

            //學校ID
            $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID',
                $school->SchoolID)->value('SchoolID');

            // 老師啟用數
            $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'T');
            })
                ->select('MemberID', 'Status')
                ->where('Status', '1')
                ->where('SchoolID', $school->SchoolID)
                ->count();

            //學生啟用數
            $studentnum = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'S');
            })
                ->select('MemberID', 'Status')
                ->where('Status', '1')
                ->where('SchoolID', $school->SchoolID)
                ->count();

            //家長啟用數
            $patriarchnum = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'P');
            })
                ->select('MemberID', 'Status')
                ->where('Status', '1')
                ->where('SchoolID', $school->SchoolID)
                ->count();

            // 老師登入數
            $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                ->where('IDLevel', 'T')
                ->where('SchoolID', $school->SchoolID)
                ->groupBy(DB::raw('year(RegisterTime)'))
                ->get();

            foreach ($teachlogintables as $item) {
                $teachlogintableTime[] = $item->year;
                $teachlogintableData[] = $item->total;
            }

            //課程總數
            $curriculum = Course::query()->select('SchoolID')
                ->where('SchoolID', $school->SchoolID)
                ->count();

            //老師上傳影片
            $uploadmovie =
                CmsResource::query()->select('member_id', 'created_dt')->whereHas('Member', function ($memberQuery) use ($school) {
                    $memberQuery->select('SchoolID')->where('SchoolID', $school->SchoolID);
                    $memberQuery->whereHas('systemauthority', function ($query) {
                        $query->where('IDLevel', 'T');
                    });
                })
                    ->count();

            //作業作品數 (需要再確認)
            $production = Course::query()->select('ConurseNO')->whereHas('Teachomework', function ($queruy) {
                $queruy->select('ClassID');
            })
                ->where('SchoolID', $school->SchoolID)
                ->count();

            //翻轉課堂數
            $overturnclass = DB::table('course')
                ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
                ->where('public_flag', '1')
                ->where('active_flag', '1')
                ->where('status', '!=', 'D')
                ->where('SchoolID', $school->SchoolID)
                ->count();

            //模擬測驗 I
            $analogytest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'I')
                ->count();

            //線上測驗 A
            $onlinetest = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'A')
                ->count();

            //班級競賽 J && K
            $interclasscompetition = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'J')
                ->orWhere('ExType', 'K')
                ->count();

            //HiTeach   H
            $HiTeach = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'H')
                ->count();

            //成績登陸    S
            $performancelogin = Exercise::query()->select('ExType')->whereHas('Course',
                function ($query) use ($school) {
                    $query->where('SchoolID', $school->SchoolID);
                })
                ->where('ExType', 'S')
                ->count();

            //合併活動 L
            $mergeactivity = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'L')
                ->count();

            //網路閱卷 O
            $onlinechecking = Exercise::query()->select('ExType')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', 'O')
                ->count();

            //學習歷程總數 EXtype != K  rule not like %k
            $alllearningprocess = Exercise::query()->select('ExType', 'Rule')->whereHas('Course', function ($query) use ($school) {
                $query->where('SchoolID', $school->SchoolID);
            })
                ->where('ExType', '!=', 'K')
                ->Where('Rule', 'not like', '%K%')
                ->count();

            //智慧教堂應用下２個圖表
            $chartdatas = $this->chartdatasNotDate();
            //题目數
            $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
                ->whereHas('Testitem', function ($TestitemQuery) use ($school) {
                    $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($school) {
                        $TestpaperQuery->select('Status')->where('Status', 'E');
                        $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($school) {
                            $MemberQuery->select('SchoolID')->where('SchoolID', $school->SchoolID);
                        });
                    });
                })
                ->where('Status', 'E')
                ->groupBy(DB::raw('year(Date)'))
                ->get();

            foreach ($subjectnums as $item) {
                $subjectnumData[] = $item["total"];
                $subjectnumTime[] = $item["year"];
            }

            //試卷數
            $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                ->whereHas('Member', function ($query) use ($school) {
                    $query->select('SchoolID')->where('SchoolID', $school->SchoolID);
                })
                ->where('Status', 'E')
                ->groupBy(DB::raw('year(CreateTime)'))
                ->get();

            foreach ($examinationnums as $item) {
                $examinationnumData[] = $item["total"];
                $examinationnumTime[] = $item["year"];
            }

            //線上測驗完成率
            //分子 有做過作業的人
            $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('exscore.AnsNum', '>', '0')
                ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
            //分母 全部但不一定有做過作業
            $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

            //線上測驗完成率
            $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

            //完成作業的人數
            $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('exscore.AnsNum', '>', '0')
                ->where('SchoolID', $school->SchoolID)->count();
            //作業作品完成率 做成作業的人/全部不一定做過作業的人
            $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

            //進行中
            $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                ->where('SchoolID', $school->SchoolID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 1)
                ->where('fc_event.public_flag', 1)
                ->where('fc_target.active_flag', 1)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();

            //進行中 to object
            foreach ($underways as $item) {
                $underwayData[] = $item->total;
                $underwayTime[] = $item->year;
            }

            //未完成
            $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                ->where('SchoolID', $school->SchoolID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 0)
                ->where('fc_event.public_flag', 0)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();
            foreach ($unfinished as $item) {

                $unfinishedData[] = $item->total;
                $unfinishedTime[] = $item->year;
            }


            //完成
            $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                ->where('SchoolID', $school->SchoolID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 1)
                ->where('fc_event.public_flag', 1)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();


            foreach ($achieves as $item) {
                $achieveData[] = $item->total;
                $achieveTime[] = $item->year;
            }
            // 全部學校
            $data[0] = $this->getNotDateList();


            $data[] = [
                'schoolname'           => $schoolname,                              //學校名稱
                'schoolid'             => $schoolid,                                //學校ID，所有為all，學校為數字ID
                'teachnum'             => $teachnum,                                //老師帳號啟用數量
                'studentnum'           => $studentnum,                              //學生帳號啟用數量
                'patriarchnum'         => $patriarchnum,                            //家長帳號啟用數量
                'dashboard'            => [
                    'teachlogintable' => [                                          //老師登入
                        'time' => $teachlogintableTime,
                        'data' => $teachlogintableData,
                    ],
                    'smartclasstable' => [
                        'curriculum'      => $curriculum,                           //課程總數
                        'uploadmovie'     => $uploadmovie,                          //上傳影片
                        'production'      => $production,                           //作業作品數
                        'overturnclass'   => $overturnclass,                        //翻轉課堂
                        'learningprocess' => [
                            'analogytest'           => $analogytest,                //模擬測驗
                            'onlinetest'            => $onlinetest,                 //線上測驗
                            'interclasscompetition' => $interclasscompetition,      //班級競賽
                            'HiTeach'               => $HiTeach,                    //HiTeach
                            'performancelogin'      => $performancelogin,           //成績登入
                            'mergeactivity'         => $mergeactivity,              //合並活動
                            'onlinechecking'        => $onlinechecking,             //網路閱卷
                            "alllearningprocess"    => $alllearningprocess,         //學習歷程總數
                        ],
                        "chartdata"       => $chartdatas,

                        //智慧教堂應用下２個圖表
                        //智慧教堂應用下２個圖表

                        "resource" => [
                            "personage"        => null,                        //个人所属
                            "areashare"        => null,                         //区级分享
                            "schoolshare"      => null,                         //校级分享
                            "overallresourece" => null,                        //总资源分享数
                            "subjectnum"       => [                                   //題目數
                                'id'   => $schoolid,
                                "time" => $subjectnumTime,
                                "data" => $subjectnumData,
                            ],
                            "examinationnum"   => [                                    //試卷數
                                'id'   => $schoolid,
                                "time" => $examinationnumTime,
                                "data" => $examinationnumData,
                            ],
                        ],
                    ],
                ],
                "textbooknum"          => [ //教材数 { "2012": 457, "2013": 865, 目前沒有教材數
                    "2014" => null,
                    "2015" => null,
                    "2016" => null,
                    "2017" => null,
                    "2018" => null,
                ],
                "study"                => [
                    "underway"     => [         //进行中
                        "percentage" => null,
                        "time"       => $underwayTime,
                        "data"       => $underwayData,

                    ],
                    "unfinished"   => [          //未完成
                        // "percentage" => null,
                        // "time"       => $unfinishedTime,
                        // "data"       => $unfinishedData,

                    ],
                    "achieve"      => [         //已完成
                        "percentage" => null,
                        "time"       => $achieveTime,
                        "data"       => $achieveData,
                    ],
                    "selfstudynum" => [    //自学任务总数
                        "percentage" => null,
                        "time"       => null,
                        "data"       => null,
                    ]
                ],
                "onlinetestcomplete"   => $onlinetestcomplete,                          //線上測驗完成率
                "productionpercentage" => $productionpercentage,                        //作業作品完成率
            ];
            //初始化
            $teachlogintableTime = [];
            $teachlogintableData = [];
            $chartTime           = [];
            $chartData           = [];
            $achieveTime         = [];
            $achieveData         = [];
            $underwayTime        = [];
            $underwayData        = [];
            $subjectnumData      = [];
            $subjectnumTime      = [];
            $examinationnumData  = [];
            $examinationnumTime  = [];
            $unfinishedTime      = [];
            $unfinishedData      = [];
        }


        return response()->json($data, 200);
    }

    //時間 學校ＩＤ必填
    private function getSchoolJson($schoolID, $StartTime, $EndTime)
    {
        //$schoolID 一定要傳schoolID
//        $schoolID  = '0';
//        $StartTime = '2011-01-10';
//        $EndTime   = '2018-01-10';

        //學校名稱
        $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID',
            $schoolID)->value('SchoolName');

        //學校ID
        $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID',
            $schoolID)->value('SchoolID');


        // 老師登入數
        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('IDLevel', 'T')
            ->where('SchoolID', $schoolID)
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();


        if (!$teachlogintables->toArray() == null) {
            foreach ($teachlogintables as $item) {
                $teachlogintableTime[] = $item->year;
                $teachlogintableData[] = $item->total;
            }
        } else {
            $teachlogintableTime = 0;
            $teachlogintableData = 0;
        }


        //學生登入數
        $studyuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'S')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $studyuserusingTotal[] = $item->total;
                $studyuserusingYear[]  = $item->year;
            }
        } else {
            $studyuserusingTotal = 0;
            $studyuserusingYear  = 0;
        }

        //家長登入數
        $patriarchuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'P')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $patriarchuserusingTotal[] = $item->total;
                $patriarchuserusingYear[]  = $item->year;
            }
        } else {
            $patriarchuserusingTotal = 0;
            $patriarchuserusingYear  = 0;
        }


        // 老師啟用數
        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })
            ->select('MemberID', 'Status')
            ->whereBetween('RegisterTime', [$StartTime, $EndTime])
            ->where('Status', '1')
            ->where('SchoolID', $schoolID)
            ->count();

        //課程總數
        $curriculum = Course::query()->select('SchoolID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->count();

        //老師上傳影片
        $uploadmovie =
            CmsResource::query()->select('member_id', 'created_dt')
                ->whereHas('Member', function ($memberQuery) use ($schoolID) {
                    $memberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                    $memberQuery->whereHas('systemauthority', function ($query) {
                        $query->where('IDLevel', 'T');
                    });
                })
                ->whereBetween('created_dt', [$StartTime, $EndTime])
                ->count();

        //作業作品數 (需要再確認)
        $production = Course::query()->select('ConurseNO')
            ->whereHas('Teachomework', function ($queruy) {
                $queruy->select('ClassID');
            })
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->count();

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->where('SchoolID', $schoolID)
            ->count();

        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->whereBetween('ExTime', [$StartTime, $EndTime])
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->groupBy(DB::raw('year(ExTime)'))
            ->get();
        if (!$chartdatas->toArray() == null) {
            foreach ($chartdatas as $item) {
                $chartData[] = $item['total'];
                $chartTime[] = $item['year'];
            }
        } else {
            $chartData = 0;
            $chartTime = 0;
        }


        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) use ($schoolID) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($schoolID) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($schoolID) {
                        $MemberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                    });
                });
            })
            ->whereBetween('Date', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();
        if (!$subjectnums->toArray() == null) {
            foreach ($subjectnums as $item) {
                $subjectnumData[] = $item["total"];
                $subjectnumTime[] = $item["year"];
            }
        } else {
            $subjectnumData = 0;
            $subjectnumTime = 0;
        }


        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) use ($schoolID) {
                $query->select('SchoolID')->where('SchoolID', $schoolID);
            })
            ->whereBetween('CreateTime', [$StartTime, $EndTime])
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        if (!$examinationnums->toArray() == null) {
            foreach ($examinationnums as $item) {
                $examinationnumData[] = $item["total"];
                $examinationnumTime[] = $item["year"];
            }
        } else {
            $examinationnumData = 0;
            $examinationnumTime = 0;
        }


        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('SchoolID', $schoolID)->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->whereBetween('CourseBTime', [$StartTime, $EndTime])
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$underways->toArray() == null) {
            //進行中 to object
            foreach ($underways as $item) {
                $underwayData[] = $item->total;
                $underwayTime[] = $item->year;
            }
        } else {
            $underwayData = 0;
            $underwayTime = 0;
        }


        //未完成
        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$unfinished->toArray() == null) {
            foreach ($unfinished as $item) {
                $unfinishedData[] = $item->total;
                $unfinishedTime[] = $item->year;
            }
        } else {
            $unfinishedData = 0;
            $unfinishedTime = 0;
        }


        //完成
        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereBetween('create_time', [$StartTime, $EndTime])
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$achieves->toArray() == null) {
            foreach ($achieves as $item) {
                $achieveData[] = $item->total;
                $achieveTime[] = $item->year;
            }
        } else {
            $achieveData = 0;
            $achieveTime = 0;
        }


        $data = [
            //默认全部数据
            'schoolid'           => $schoolid, //学校ID
            'schoolname'         => $schoolname, //学校名称
            'teachuserusing'     => [            //老师登录数
                'teachnum' => null,
                'data'     => [
                    'time' => $teachlogintableTime,
                    'data' => $teachlogintableData,
                ],
            ],
            'studyuserusing'     => [                   //学生登录数
                'studynum' => null,
                'data'     => [
                    'time' => $studyuserusingTotal,
                    'data' => $studyuserusingYear,
                ]
            ],
            'patriarchuserusing' => [                  //家长登录数
                'patriarchnum' => null,
                'data'         => [
                    'time' => $patriarchuserusingYear,
                    'data' => $patriarchuserusingTotal,
                ]
            ],
            'teachnum'           => [               //老师情况
                'teach'      => null,
                'data'       => $teachnum,
//            ],
                'studynum'   => [               //学生登录数
                    'time' => $studyuserusingTotal,
                    'data' => $studyuserusingYear,
                ],
                'curriculum' => [               //课程详情
                    'curriculum' => null,
                    'data'       => $curriculum,
                ],
                'dashboard'  => [
                    'smartclasstable' => [
                        'curriculum'       => $curriculum, //课程总数
                        'electronicalnote' => null, //电子笔记
                        'uploadmovie'      => $uploadmovie, //上传影片
                        'production'       => $production, //作业作品数
                        'overturnclass'    => $overturnclass, //翻转课堂
                        'testissue'        => null, //测验发布
                        'learningprocess'  => [
                            'analogytest'           => $analogytest, //模拟测验
                            'onlinetest'            => $onlinetest, //线上测验
                            'interclasscompetition' => $interclasscompetition, //班际竞赛
                            'HiTeach'               => $HiTeach, //HiTeach
                            'performancelogin'      => $performancelogin, //成绩登录
                            'mergeactivity'         => $mergeactivity, //合并活动
                            'onlinechecking'        => $onlinechecking, //网络阅卷
                            'alllearningprocess'    => $alllearningprocess, //学习历程总数
                        ],
                        'chartdata'        => [
                            'id'   => $schoolid,
                            'time' => $chartTime,
                            'data' => $chartData,
                        ],
                    ],
                ],
                'resource'   => [
                    'personage'        => null, //个人自有
                    'areashare'        => null, //区级分享
                    'schoolshare'      => null, //校级分享
                    'overallresourece' => null, //总资源分享数
                    'subjectnum'       => [ //题目数
                        'time' => $subjectnumTime,
                        'data' => $subjectnumData,
                    ],
                    'examinationnum'   => [ //试卷数
                        'time' => $examinationnumTime,
                        'data' => $examinationnumData,
                    ],
                    'textbooknum'      => [        //教材数
                        'time' => null,
                        'data' => null,
                    ],
                ],
                'study'      => [
                    'underway'             => [
                        'percentage' => null,
                        'data'       => [ //进行中
                            'time' => $underwayTime,
                            'data' => $underwayData,
                        ]
                    ],
                    'unfinished'           => [
                        'percentage' => null,
                        'data'       => [ //未完成
                            'time' => $unfinishedTime,
                            'data' => $unfinishedData,
                        ],
                    ],
                    'achieve'              => [
                        'percentage' => null,
                        'data'       => [ //已完成
                            'time' => $achieveTime,
                            'data' => $achieveData,
                        ]
                    ],
                    'selfstudynum'         => [
                        'studytotal' => null,
                        'data'       => [ //自学任务总数
                            'time' => null,
                            'data' => null,
                        ]
                    ],
                    'onlinetest'           => $onlinetest, //线上测验完成率
                    'productionpercentage' => $productionpercentage //作业作品完成率
                ],
            ],
        ];
        return response()->json($data, 200);
    }

    //不篩選時間
    private function getSchooIDNotDateJson($schoolID)
    {
        //學校名稱
        $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID',
            $schoolID)->value('SchoolName');

        //學校ID
        $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID',
            $schoolID)->value('SchoolID');


        // 老師登入數
        $teachlogintables = DB::table('hiteach_log')->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->where('IDLevel', 'T')
            ->where('SchoolID', $schoolID)
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();


        if (!$teachlogintables->toArray() == null) {
            foreach ($teachlogintables as $item) {
                $teachlogintableTime[] = $item->year;
                $teachlogintableData[] = $item->total;
            }
        }
        //學生登入數
        $studyuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'S')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $studyuserusingTotal[] = $item->total;
                $studyuserusingYear[]  = $item->year;
            }
        } else {
            $studyuserusingTotal = 0;
            $studyuserusingYear  = 0;
        }

        //家長登入數
        $patriarchuserusings = DB::table('api_a1_log')->select(DB::raw('count(year(RegisterTime)) total ,year(RegisterTime) year'))
            ->join('member', 'api_a1_log.MemberID', '=', 'member.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.MemberID')
            ->where('IDLevel', 'P')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
        if (!$studyuserusings->toArray() == null) {
            foreach ($studyuserusings as $item) {
                $patriarchuserusingTotal[] = $item->total;
                $patriarchuserusingYear[]  = $item->year;
            }
        } else {
            $patriarchuserusingTotal = 0;
            $patriarchuserusingYear  = 0;
        }

        // 老師啟用數
        $teachnum = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })
            ->select('MemberID', 'Status')
            ->where('Status', '1')
            ->where('SchoolID', $schoolID)
            ->count();

        //課程總數
        $curriculum = Course::query()->select('SchoolID')
            ->where('SchoolID', $schoolID)
            ->count();

        //老師上傳影片
        $uploadmovie =
            CmsResource::query()->select('member_id', 'created_dt')
                ->whereHas('Member', function ($memberQuery) use ($schoolID) {
                    $memberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                    $memberQuery->whereHas('systemauthority', function ($query) {
                        $query->where('IDLevel', 'T');
                    });
                })
                ->count();

        //作業作品數 (需要再確認)
        $production = Course::query()->select('ConurseNO')
            ->whereHas('Teachomework', function ($queruy) {
                $queruy->select('ClassID');
            })
            ->where('SchoolID', $schoolID)
            ->count();

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->where('SchoolID', $schoolID)
            ->count();

        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
            ->whereHas('Course', function ($query) use ($schoolID) {
                $query->where('SchoolID', $schoolID);
            })
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->groupBy(DB::raw('year(ExTime)'))
            ->get();
        if (!$chartdatas->toArray() == null) {
            foreach ($chartdatas as $item) {
                $chartData[] = $item['total'];
                $chartTime[] = $item['year'];
            }
        } else {
            $chartData = 0;
            $chartTime = 0;
        }


        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) use ($schoolID) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($schoolID) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($schoolID) {
                        $MemberQuery->select('SchoolID')->where('SchoolID', $schoolID);
                    });
                });
            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();
        if (!$subjectnums->toArray() == null) {
            foreach ($subjectnums as $item) {
                $subjectnumData[] = $item["total"];
                $subjectnumTime[] = $item["year"];
            }
        } else {
            $subjectnumData = 0;
            $subjectnumTime = 0;
        }


        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) use ($schoolID) {
                $query->select('SchoolID')->where('SchoolID', $schoolID);
            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        if (!$examinationnums->toArray() == null) {
            foreach ($examinationnums as $item) {
                $examinationnumData[] = $item["total"];
                $examinationnumTime[] = $item["year"];
            }
        } else {
            $examinationnumData = 0;
            $examinationnumTime = 0;
        }


        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('SchoolID', $schoolID)->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('SchoolID', $schoolID)->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$underways->toArray() == null) {
            //進行中 to object
            foreach ($underways as $item) {
                $underwayData[] = $item->total;
                $underwayTime[] = $item->year;
            }
        } else {
            $underwayData = 0;
            $underwayTime = 0;
        }


        //未完成
        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$unfinished->toArray() == null) {
            foreach ($unfinished as $item) {
                $unfinishedData[] = $item->total;
                $unfinishedTime[] = $item->year;
            }
        } else {
            $unfinishedData = 0;
            $unfinishedTime = 0;
        }


        //完成
        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('SchoolID', $schoolID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$achieves->toArray() == null) {
            foreach ($achieves as $item) {
                $achieveData[] = $item->total;
                $achieveTime[] = $item->year;
            }
        } else {
            $achieveData = 0;
            $achieveTime = 0;
        }


        $data[] = [
            //默认全部数据
            'schoolid'           => $schoolid, //学校ID
            'schoolname'         => $schoolname, //学校名称
            'teachuserusing'     => [               //老师登录数
                'teachnum' => null,
                'data'     => [
                    'time' => $teachlogintableTime,
                    'data' => $teachlogintableData,
                ],
            ],
            'studyuserusing'     => [                   //学生登录数
                'studynum' => null,
                'data'     => [
                    'time' => $studyuserusingYear,
                    'data' => $studyuserusingTotal,
                ]
            ],
            'patriarchuserusing' => [                     //家长登录数
                'patriarchnum' => null,
                'data'         => [
                    'time' => $patriarchuserusingTotal,
                    'data' => $patriarchuserusingYear,
                ]
            ],
            'teachnum'           => [                       //老师情况
                'teach'      => null,
                'data'       => $teachnum,
//            ],
                'studynum'   => [                            //学生登录数
                    'time' => $studyuserusingYear,
                    'data' => $studyuserusingTotal,
                ],
                'curriculum' => [               //课程详情
                    'curriculum' => null,
                    'data'       => $curriculum,
                ],
                'dashboard'  => [
                    'smartclasstable' => [
                        'curriculum'       => $curriculum, //课程总数
                        'electronicalnote' => null, //电子笔记
                        'uploadmovie'      => $uploadmovie, //上传影片
                        'production'       => $production, //作业作品数
                        'overturnclass'    => $overturnclass, //翻转课堂
                        'testissue'        => null, //测验发布
                        'learningprocess'  => [
                            'analogytest'           => $analogytest, //模拟测验
                            'onlinetest'            => $onlinetest, //线上测验
                            'interclasscompetition' => $interclasscompetition, //班际竞赛
                            'HiTeach'               => $HiTeach, //HiTeach
                            'performancelogin'      => $performancelogin, //成绩登录
                            'mergeactivity'         => $mergeactivity, //合并活动
                            'onlinechecking'        => $onlinechecking, //网络阅卷
                            'alllearningprocess'    => $alllearningprocess, //学习历程总数
                        ],
                        'chartdata'        => [
                            'id'   => $schoolid,
                            'time' => $chartTime,
                            'data' => $chartData,
                        ],
                    ],
                ],
                'resource'   => [
                    'personage'        => null, //个人自有
                    'areashare'        => null, //区级分享
                    'schoolshare'      => null, //校级分享
                    'overallresourece' => null, //总资源分享数
                    'subjectnum'       => [ //题目数
                        'time' => $subjectnumTime,
                        'data' => $subjectnumData,
                    ],
                    'examinationnum'   => [ //试卷数
                        'time' => $examinationnumTime,
                        'data' => $examinationnumData,
                    ],
                    'textbooknum'      => [        //教材数
                        'time' => null,
                        'data' => null,
                    ],
                ],
                'study'      => [
                    'underway'             => [
                        'percentage' => null,
                        'data'       => [ //进行中
                            'time' => $underwayTime,
                            'data' => $underwayData,
                        ]
                    ],
                    'unfinished'           => [
                        'percentage' => null,
                        'data'       => [ //未完成
                            'time' => $unfinishedTime,
                            'data' => $unfinishedData,
                        ],
                    ],
                    'achieve'              => [
                        'percentage' => null,
                        'data'       => [ //已完成
                            'time' => $achieveTime,
                            'data' => $achieveData,
                        ]
                    ],
                    'selfstudynum'         => [
                        'studytotal' => null,
                        'data'       => [ //自学任务总数
                            'time' => null,
                            'data' => null,
                        ]
                    ],
                    'onlinetest'           => $onlinetest, //线上测验完成率
                    'productionpercentage' => $productionpercentage //作业作品完成率
                ],
            ],
        ];
        //初始化
        $teachlogintableTime     = [];
        $teachlogintableData     = [];
        $chartTime               = [];
        $chartData               = [];
        $achieveTime             = [];
        $achieveData             = [];
        $underwayTime            = [];
        $underwayData            = [];
        $subjectnumData          = [];
        $subjectnumTime          = [];
        $examinationnumData      = [];
        $examinationnumTime      = [];
        $unfinishedTime          = [];
        $unfinishedData          = [];
        $patriarchuserusingTotal = [];
        $patriarchuserusingYear  = [];
        $studyuserusingYear      = [];
        $studyuserusingTotal     = [];

        return response()->json($data, 200);
    }


    //顯示老師資訊 尚無資訊
    private function teacher(Request $request)
    {
        $members = Member::select('MemberID')->where('MemberID', '<', '10000')->get();
        $schools = SchoolInfo::select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($members as $member) {

            // $MemberID = $member->MemberID;


            //會員ＩＤ
            // $MemberID = $request->MemberID;


            //老師名稱
            $teachname = Member::where('MemberID', $member->MemberID)->value('RealName');

            //課程總數
            $curriculum = Course::query()->where('MemberID', $member->MemberID)->count();


            //老師上傳影片
            $uploadmovie = CmsResource::query()->select('member_id', 'created_dt')
                ->whereHas('Member', function ($memberQuery) use ($members) {
                    $memberQuery->where('MemberID', $member->MemberID);
                    $memberQuery->whereHas('systemauthority', function ($query) {
                        $query->where('IDLevel', 'T');
                    });
                })->count();

            //作業作品數 (需要再確認)
            $production = Course::query()->select('ConurseNO')
                ->whereHas('Teachomework', function ($queruy) {
                    $queruy->select('ClassID');
                })
                ->where('MemberID', $member->MemberID)
                ->count();

            //翻轉課堂數
            $overturnclass = DB::table('course')
                ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
                ->where('public_flag', '1')
                ->where('active_flag', '1')
                ->where('status', '!=', 'D')
                ->where('course.MemberID', $member->MemberID)
                ->count();

            //模擬測驗 I
            $analogytest = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'I')
                ->count();

            //線上測驗 A
            $onlinetest = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'A')
                ->count();

            //班級競賽 J && K
            $interclasscompetition = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'J')
                ->orWhere('ExType', 'K')
                ->count();

            //HiTeach   H
            $HiTeach = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'H')
                ->count();

            //成績登陸    S
            $performancelogin = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'S')
                ->count();

            //合併活動 L
            $mergeactivity = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'L')
                ->count();

            //網路閱卷 O
            $onlinechecking = Exercise::query()->select('ExType')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', 'O')
                ->count();

            //學習歷程總數 EXtype != K  rule not like %k
            $alllearningprocess = Exercise::query()->select('ExType', 'Rule')
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', '!=', 'K')
                ->Where('Rule', 'not like', '%K%')
                ->count();

            //智慧教堂應用下２個圖表
            $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
                ->whereHas('Course', function ($query) use ($members) {
                    $query->where('MemberID', $member->MemberID);
                })
                ->where('ExType', '!=', 'K')
                ->Where('Rule', 'not like', '%K%')
                ->groupBy(DB::raw('year(ExTime)'))
                ->get();
            if (!$chartdatas->toArray() == null) {
                foreach ($chartdatas as $item) {
                    $chartData[] = $item['total'];
                    $chartTime[] = $item['year'];
                }
            } else {
                $chartData = 0;
                $chartTime = 0;
            }


            //题目數
            $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
                ->whereHas('Testitem', function ($TestitemQuery) use ($members) {
                    $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($members) {
                        $TestpaperQuery->select('Status')->where('Status', 'E');
                        $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($members) {
                            $MemberQuery->select('SchoolID')->where('MemberID', $member->MemberID);
                        });
                    });
                })
                ->where('Status', 'E')
                ->groupBy(DB::raw('year(Date)'))
                ->get();
            if (!$subjectnums->toArray() == null) {
                foreach ($subjectnums as $item) {
                    $subjectnumData[] = $item["total"];
                    $subjectnumTime[] = $item["year"];
                }
            } else {
                $subjectnumData = 0;
                $subjectnumTime = 0;
            }


            //試卷數
            $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                ->whereHas('Member', function ($query) use ($members) {
                    $query->select('SchoolID')->where('MemberID', $member->MemberID);
                })
                ->where('Status', 'E')
                ->groupBy(DB::raw('year(CreateTime)'))
                ->get();

            if (!$examinationnums->toArray() == null) {
                foreach ($examinationnums as $item) {
                    $examinationnumData[] = $item["total"];
                    $examinationnumTime[] = $item["year"];
                }
            } else {
                $examinationnumData = 0;
                $examinationnumTime = 0;
            }

            //線上測驗完成率
            //分子 有做過作業的人
            $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('exscore.AnsNum', '>', '0')
                ->where('course.MemberID', $member->MemberID)->count('exscore.ExNO');

            //分母 全部但不一定有做過作業
            $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('course.MemberID', $member->MemberID)->count('major.CourseNO');

            //線上測驗完成率
            $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

            //完成作業的人數
            $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
                ->where('ExType', 'A')
                ->where('Rule', 'not like', '%K%')
                ->where('exscore.AnsNum', '>', '0')
                ->where('course.MemberID', $member->MemberID)->count();
            //作業作品完成率 做成作業的人/全部不一定做過作業的人
            $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

            //進行中
            $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                ->where('member.MemberID', $member->MemberID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 1)
                ->where('fc_event.public_flag', 1)
                ->where('fc_target.active_flag', 1)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();

            if (!$underways->toArray() == null) {
                //進行中 to object
                foreach ($underways as $item) {
                    $underwayData[] = $item->total;
                    $underwayTime[] = $item->year;
                }
            } else {
                $underwayData = 0;
                $underwayTime = 0;
            }


            //未完成
            $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
                ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                ->where('member.MemberID', $member->MemberID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 0)
                ->where('fc_event.public_flag', 0)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();
            if (!$unfinished->toArray() == null) {
                foreach ($unfinished as $item) {
                    $unfinishedData[] = $item->total;
                    $unfinishedTime[] = $item->year;
                }
            } else {
                $unfinishedData = 0;
                $unfinishedTime = 0;
            }


            //完成
            $achieves = DB::table('fc_sub_event')->selectRaw('count(year(create_time)) as total,year(create_time) as year')
                ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                ->where('member.MemberID', $member->MemberID)
                ->where('fc_event.status', '!=', 'D')
                ->where('fc_event.active_flag', 1)
                ->where('fc_event.public_flag', 1)
                ->groupBy(DB::raw('year(create_time)'))
                ->get();

            if (!$achieves->toArray() == null) {
                foreach ($achieves as $item) {
                    $achieveData[] = $item->total;
                    $achieveTime[] = $item->year;
                }
            } else {
                $achieveData = 0;
                $achieveTime = 0;
            }


            $data[] = [
                'teach'      => [ //点击某个老师出现的详细数据
                    'id'                   => $member->MemberID,
                    'teachname'            => $teachname,
                    'data'                 => [
                        'curriculum'       => $curriculum,     //课程总数
                        'electronicalnote' => null,             //电子笔记
                        'uploadmovie'      => $uploadmovie,     //上传影片
                        'production'       => $production,     //作业作品数
                        'overturnclass'    => $overturnclass,     //翻转课堂
                        'testissue'        => null,     //测验发布
                    ],
                    'teachlearningprocess' => [
                        'analogytest'           => $analogytest, //模拟测验
                        'onlinetest'            => $onlinetest, //线上测验
                        'interclasscompetition' => $interclasscompetition, //班际竞赛
                        'HiTeach'               => $HiTeach, //HiTeach
                        'performancelogin'      => $performancelogin, //成绩登录
                        'mergeactivity'         => $mergeactivity, //合并活动
                        'onlinechecking'        => $onlinechecking, //网络阅卷
                        'alllearningprocess'    => $alllearningprocess, //学习历程总数
                    ],
                    'teachchartdata'       => [
                        'id'   => $member->MemberID,
                        'time' => $chartTime,
                        'data' => $chartData,
                    ],
                    'personage'            => null, //个人自有
                    'areashare'            => null, //区级分享
                    'schoolshare'          => null, //校级分享
                    'overallresourece'     => null, //总资源分享数
                    'subjectnum'           => [ //题目数
                        'time' => $subjectnumTime,
                        'data' => $subjectnumData,
                    ],
                    'examinationnum'       => [ //试卷数
                        'time' => $examinationnumTime,
                        'data' => $examinationnumData,
                    ],
                    'textbooknum'          => [        //教材数
                        'time' => null,
                        'data' => null,
                    ],
                ],
                'curriculum' => [ //点击课程呈现的数据
                    'classid'              => null,
                    'classname'            => null,
                    'data'                 => [
                        'curriculum'        => null, // 课程总数  'curriculum'       => $curriculum,
                        'electronicalnote ' => null, // 电子笔记  'electronicalnote' => null,
                        'uploadmovie'       => null, // 上传影片  'uploadmovie'      => $uploadmovie,
                        'production'        => null, // 作业作品数  'production'       => $production,     /
                        'overturnclass'     => null, // 翻转课堂  'overturnclass'    => $overturnclass,
                        'testissue'         => null, // 测验发布  'testissue'        => null,
                    ],
                    'teachlearningprocess' => [
                        'analogytest'           => null, //模拟测验
                        'onlinetest'            => null, //线上测验
                        'interclasscompetition' => null, //班际竞赛
                        'HiTeach'               => null, //HiTeach
                        'performancelogin'      => null, //成绩登录
                        'mergeactivity'         => null, //合并活动
                        'onlinechecking'        => null, //网络阅卷
                        'alllearningprocess'    => null, //学习历程总数
                    ],
                    'teachchartdata'       => [
                        [
                            'id'   => null,
                            'time' => '',
                            'data' => '',
                        ],
                    ],
                    'underway'             => [
                        'percentage' => null,
                        'data'       => [ //进行中
                            'time' => '',
                            'data' => '',
                        ]
                    ],
                    'unfinished'           => [
                        'percentage' => null,
                        'data'       => [ //未完成
                            'time' => $unfinishedTime,
                            'data' => $unfinishedData,
                        ],
                    ],
                    'achieve'              => [
                        'percentage' => null,
                        'data'       => [ //已完成
                            'time' => $achieveTime,
                            'data' => $achieveData,
                        ]
                    ],
                    'selfstudynum'         => [
                        'studytotal' => '',
                        'data'       => [ //自学任务总数
                            'time' => null,
                            'data' => null,
                        ]
                    ],
                    'onlinetest'           => $onlinetest, //线上测验完成率
                ],
            ];

            // dd($data);
            //     $teachlogintableTime     = [];
            //     $teachlogintableData     = [];
            //     $chartTime               = [];
            //     $chartData               = [];
            //     $achieveTime             = [];
            //     $achieveData             = [];
            //     $underwayTime            = [];
            //     $underwayData            = [];
            //     $subjectnumData          = [];
            //     $subjectnumTime          = [];
            //     $examinationnumData      = [];
            //     $examinationnumTime      = [];
            //     $unfinishedTime          = [];
            //     $unfinishedData          = [];
            //     $patriarchuserusingTotal = [];
            //     $patriarchuserusingYear  = [];
            //     $studyuserusingYear      = [];
            //     $studyuserusingTotal     = [];
        }
        var_dump($data);
        dd($data);
        return response()->json($data, 200);

    }

    // 課程資訊
    public function course(Request $request)
    {
        // $MemberID = 9022;
        //課程ＩＤ
        $CourseNO = $request->CourseNO;
        //課程名稱
        $CourseName = Course::where('CourseNO', $CourseNO)->value('CourseName');

        //課程總數
        $curriculum = Course::query()->where('CourseNO', $CourseNO)->value('CourseCount');

        //老師上傳影片(需在觀察)
        $uploadmovie = DB::table('cms_resource')
            ->join('cms_material', 'cms_material.rid', '=', 'cms_resource.rid')
            ->join('exercise', 'exercise.CourseNO', '=', 'cms_material.ExNO')
            ->where('cms_resource.flag', 1)
            ->where('exercise.CourseNO', $CourseNO)
            ->count();
        dd($uploadmovie);


        //作業作品數 (需要再確認)
        $production = Course::query()->select('CourseNO')
            ->whereHas('Teachomework', function ($queruy) {
                $queruy->select('ClassID');
            })
            ->where('MemberID', $MemberID)
            ->toSql();
        dd($production);

        //翻轉課堂數
        $overturnclass = DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('status', '!=', 'D')
            ->where('MemberID', $MemberID)
            ->count();
        dd($overturnclass);
        //模擬測驗 I
        $analogytest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'I')
            ->count();

        //線上測驗 A
        $onlinetest = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'A')
            ->count();

        //班級競賽 J && K
        $interclasscompetition = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'J')
            ->orWhere('ExType', 'K')
            ->count();

        //HiTeach   H
        $HiTeach = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'H')
            ->count();

        //成績登陸    S
        $performancelogin = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'S')
            ->count();

        //合併活動 L
        $mergeactivity = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'L')
            ->count();

        //網路閱卷 O
        $onlinechecking = Exercise::query()->select('ExType')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', 'O')
            ->count();

        //學習歷程總數 EXtype != K  rule not like %k
        $alllearningprocess = Exercise::query()->select('ExType', 'Rule')
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->count();

        //智慧教堂應用下２個圖表
        $chartdatas = Exercise::query()->select(DB::raw('count(year(ExTime)) as total ,year(ExTime) as year'))
            ->whereHas('Course', function ($query) use ($MemberID) {
                $query->where('MemberID', $MemberID);
            })
            ->where('ExType', '!=', 'K')
            ->Where('Rule', 'not like', '%K%')
            ->groupBy(DB::raw('year(ExTime)'))
            ->get();
        if (!$chartdatas->toArray() == null) {
            foreach ($chartdatas as $item) {
                $chartData[] = $item['total'];
                $chartTime[] = $item['year'];
            }
        } else {
            $chartData = 0;
            $chartTime = 0;
        }


        //题目數
        $subjectnums = Iteminfo::query()->select(DB::raw('count(year(Date)) as total,year(Date) as year'))
            ->whereHas('Testitem', function ($TestitemQuery) use ($MemberID) {
                $TestitemQuery->whereHas('Testpaper', function ($TestpaperQuery) use ($MemberID) {
                    $TestpaperQuery->select('Status')->where('Status', 'E');
                    $TestpaperQuery->whereHas('Member', function ($MemberQuery) use ($MemberID) {
                        $MemberQuery->select('SchoolID')->where('MemberID', $MemberID);
                    });
                });
            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(Date)'))
            ->get();
        if (!$subjectnums->toArray() == null) {
            foreach ($subjectnums as $item) {
                $subjectnumData[] = $item["total"];
                $subjectnumTime[] = $item["year"];
            }
        } else {
            $subjectnumData = 0;
            $subjectnumTime = 0;
        }


        //試卷數
        $examinationnums = Testpaper::query()->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
            ->whereHas('Member', function ($query) use ($MemberID) {
                $query->select('SchoolID')->where('MemberID', $MemberID);
            })
            ->where('Status', 'E')
            ->groupBy(DB::raw('year(CreateTime)'))
            ->get();

        if (!$examinationnums->toArray() == null) {
            foreach ($examinationnums as $item) {
                $examinationnumData[] = $item["total"];
                $examinationnumTime[] = $item["year"];
            }
        } else {
            $examinationnumData = 0;
            $examinationnumTime = 0;
        }


        //線上測驗完成率
        //分子 有做過作業的人
        $Molecular = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('MemberID', $MemberID)->count('exscore.ExNO');
        //分母 全部但不一定有做過作業
        $Denominator = DB::table('course')->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('MemberID', $MemberID)->count('major.CourseNO');

        //線上測驗完成率
        $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

        //完成作業的人數
        $complete = DB::table('course')->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
            ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
            ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
            ->join('stuhomework', 'exscore.MemberID', '=', 'stuhomework.MemberID')
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->where('exscore.AnsNum', '>', '0')
            ->where('MemberID', $MemberID)->count();
        //作業作品完成率 做成作業的人/全部不一定做過作業的人
        $productionpercentage = ($Denominator == 0) ? 0 : intval(($complete / $Denominator) * 100);

        //進行中
        $underways = DB::table('fc_target')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->where('MemberID', $MemberID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$underways->toArray() == null) {
            //進行中 to object
            foreach ($underways as $item) {
                $underwayData[] = $item->total;
                $underwayTime[] = $item->year;
            }
        } else {
            $underwayData = 0;
            $underwayTime = 0;
        }


        //未完成
        $unfinished = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('MemberID', $MemberID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();
        if (!$unfinished->toArray() == null) {
            foreach ($unfinished as $item) {
                $unfinishedData[] = $item->total;
                $unfinishedTime[] = $item->year;
            }
        } else {
            $unfinishedData = 0;
            $unfinishedTime = 0;
        }


        //完成
        $achieves = DB::table('fc_sub_event')->select(DB::raw('count(year(create_time)) as total,year(create_time) as year'))
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->where('MemberID', $MemberID)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->groupBy(DB::raw('year(create_time)'))
            ->get();

        if (!$achieves->toArray() == null) {
            foreach ($achieves as $item) {
                $achieveData[] = $item->total;
                $achieveTime[] = $item->year;
            }
        } else {
            $achieveData = 0;
            $achieveTime = 0;
        }


        $data                    = [
            /*   'teach'      => [ //点击某个老师出现的详细数据
                   'id'                   => $MemberID,
                   'teachname'            => $teachname,
                   'data'                 => [
                       'curriculum'       => $curriculum,     //课程总数
                       'electronicalnote' => null,     //电子笔记
                       'uploadmovie'      => $uploadmovie,     //上传影片
                       'production'       => $production,     //作业作品数
                       'overturnclass'    => $overturnclass,     //翻转课堂
                       'testissue'        => null,     //测验发布
                   ],
                   'teachlearningprocess' => [
                       'analogytest'           => $analogytest, //模拟测验
                       'onlinetest'            => $onlinetest, //线上测验
                       'interclasscompetition' => $interclasscompetition, //班际竞赛
                       'HiTeach'               => $HiTeach, //HiTeach
                       'performancelogin'      => $performancelogin, //成绩登录
                       'mergeactivity'         => $mergeactivity, //合并活动
                       'onlinechecking'        => $onlinechecking, //网络阅卷
                       'alllearningprocess'    => $alllearningprocess, //学习历程总数
                   ],
                   'teachchartdata'       => [
                       'id'   => $MemberID,
                       'time' => $chartTime,
                       'data' => $chartData,
                   ],
                   'personage'            => null, //个人自有
                   'areashare'            => null, //区级分享
                   'schoolshare'          => null, //校级分享
                   'overallresourece'     => null, //总资源分享数
                   'subjectnum'           => [ //题目数
                       'time' => $subjectnumTime,
                       'data' => $subjectnumData,
                   ],
                   'examinationnum'       => [ //试卷数
                       'time' => $examinationnumTime,
                       'data' => $examinationnumData,
                   ],
                   'textbooknum'          => [        //教材数
                       'time' => null,
                       'data' => null,
                   ],
               ],*/
            'curriculum' => [ //点击课程呈现的数据
                [
                    'classid'              => $CourseNO,        //課程ＩＤ
                    'classname'            => $CourseName,      //課程名稱
                    'data'                 => [
                        'curriculum'       => $curriculum,      //课程总数
                        'electronicalnote' => null,             //电子笔记
                        'uploadmovie'      => $uploadmovie,     //上传影片
                        'production'       => $production,      //作业作品数
                        'overturnclass'    => $overturnclass,   //翻转课堂
                        'testissue'        => null,             //测验发布
                    ],
                    'teachlearningprocess' => [
                        'analogytest'           => $analogytest, //模拟测验
                        'onlinetest'            => $onlinetest, //线上测验
                        'interclasscompetition' => $interclasscompetition, //班际竞赛
                        'HiTeach'               => $HiTeach, //HiTeach
                        'performancelogin'      => $performancelogin, //成绩登录
                        'mergeactivity'         => $mergeactivity, //合并活动
                        'onlinechecking'        => $onlinechecking, //网络阅卷
                        'alllearningprocess'    => $alllearningprocess, //学习历程总数
                    ],
                    'teachchartdata'       => [
                        [
                            'id'   => null,
                            'time' => '',
                            'data' => '',
                        ],
                    ],
                    'underway'             => [
                        'percentage' => null,
                        'data'       => [ //进行中
                            'time' => '',
                            'data' => '',
                        ]
                    ],
                    'unfinished'           => [
                        'percentage' => null,
                        'data'       => [ //未完成
                            'time' => $unfinishedTime,
                            'data' => $unfinishedData,
                        ],
                    ],
                    'achieve'              => [
                        'percentage' => null,
                        'data'       => [ //已完成
                            'time' => $achieveTime,
                            'data' => $achieveData,
                        ]
                    ],
                    'selfstudynum'         => [
                        'studytotal' => null,
                        'data'       => [ //自学任务总数
                            'time' => null,
                            'data' => null,
                        ]
                    ],
                    'onlinetest'           => $onlinetest, //线上测验完成率
                    'productionpercentage' => $productionpercentage //作业作品完成率
                ],
            ],
        ];
        $teachlogintableTime     = [];
        $teachlogintableData     = [];
        $chartTime               = [];
        $chartData               = [];
        $achieveTime             = [];
        $achieveData             = [];
        $underwayTime            = [];
        $underwayData            = [];
        $subjectnumData          = [];
        $subjectnumTime          = [];
        $examinationnumData      = [];
        $examinationnumTime      = [];
        $unfinishedTime          = [];
        $unfinishedData          = [];
        $patriarchuserusingTotal = [];
        $patriarchuserusingYear  = [];
        $studyuserusingYear      = [];
        $studyuserusingTotal     = [];
        return response()->json($data, 200);

    }
}

