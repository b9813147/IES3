<?php /** @noinspection ALL */

namespace App\Http\Controllers\Admin\Api;

use App\Models\ApiDistricts;
use App\Models\CmsResource;
use App\Models\Course;
use App\Models\District_schools;
use App\Models\DistrictsAllSchool;
use App\Models\Exercise;
use App\Models\Iteminfo;
use App\Models\Member;
use App\Models\SchoolInfo;
use App\Models\Teaching_resource;
use App\Models\Testpaper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Supports\HashIdSupport;

class RepeaterController extends Controller
{
    use HashIdSupport;

    public function run()
    {

        $this->updateDistrictsDown();
        $this->updateDistrictsUp();
        $this->updateSemesterDown();
        $this->updateSemesterUp();

        return '更新完成';
    }

    public function destroy()
    {
        ApiDistricts::query()->truncate();
        DistrictsAllSchool::query()->truncate();

        return '清除完成';
    }

    /**
     * 更新所有上學期
     * 學校資訊
     */
    public function updateSemesterUp()
    {

        // 起始時間年份
        // 起始時間
        $start = DB::table('member')->selectRaw('year(RegisterTime) year')->groupBy(DB::raw('year(RegisterTime)'))->get();
        //學校ID
        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        //當下年份
        $nowYear = Carbon::now()->format('Y');
        foreach ($start as $item) {
            if ($item->year) {
                foreach ($schools as $school) {

                    // if ($item->year <= $now) {
                    //     if (isset($item->year)) {
                    $StartTime = $item->year . '-08-01';
                    $EndTime   = ($item->year + 1 == $nowYear) ? $item->year + 1 . '-01-31' : '';
                    $year      = $item->year;

                    //學校名稱
                    $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID', $school->SchoolID)->value('SchoolName');

                    //學校ID
                    $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID', $school->SchoolID)->value('SchoolID');
                    //學區ID
                    $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID', $school->SchoolID)->value('SchoolID');

                    // 老師啟用數
                    $teachnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->select('MemberID', 'Status')
                        ->where('systemauthority.IDLevel', 'T')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //學生啟用數
                    $studentnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->where('systemauthority.IDLevel', 'S')
                        ->select('MemberID', 'Status')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //家長啟用數
                    $patriarchnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->where('systemauthority.IDLevel', 'P')
                        ->select('MemberID', 'Status')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    // 老師登入數
                    $teachlogintables = DB::table('hiteach_log')
                        ->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                        ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                        ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('IDLevel', 'T')
                        ->where('SchoolID', $school->SchoolID)
                        ->groupBy(DB::raw('year(RegisterTime)'))
                        ->get();

                    //學生登入數
                    $studylogintable = DB::table('api_a1_log')
                        ->selectRaw('count(distinct (api_a1_log.MemberID)) total, year(api_a1_log.CreateTime) year')
                        ->join('member', 'member.memberID', '=', 'api_a1_log.MemberID')
                        ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.memberID')
                        ->whereBetween('api_a1_log.CreateTime', [$StartTime, $EndTime])
                        ->where('systemauthority.IDLevel', '=', 'S')
                        ->where('member.SchoolID', '=', $school->SchoolID)
                        ->groupBy(DB::raw('year(api_a1_log.CreateTime)'))
                        ->get();

                    //課程總數
                    $curriculum = Course::query()->select('SchoolID')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //老師上傳影片
                    $uploadmovie =
                        CmsResource::query()->select('member_id', 'created_dt')
                            ->join('member', 'member.MemberID', 'cms_resource.member_id')
                            ->join('systemauthority', 'member.MemberID', 'systemauthority.MemberID')
                            ->where('member.SchoolID', $school->SchoolID)
                            ->where('systemauthority.IDLevel', 'T')
                            ->whereBetween('created_dt', [$StartTime, $EndTime])
                            ->count();

                    //作業作品數 (需要再確認)
                    $production = Course::query()->select('CourseNO')
                        ->join('teahomework', 'teahomework.ClassID', 'course.CourseNO')
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
                    $analogytest = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'I')
                        ->count();

                    //線上測驗 A
                    $onlinetest = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->count();

                    //班級競賽 J && K
                    $interclasscompetition = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->whereIn('ExType', ['J', 'K'])
                        ->count();

                    //HiTeach   H
                    $HiTeach = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'H')
                        ->count();

                    //成績登陸    S
                    $performancelogin = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'S')
                        ->count();

                    //合併活動 L
                    $mergeactivity = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'L')
                        ->count();

                    //網路閱卷 O
                    $onlinechecking = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'O')
                        ->count();

                    //學習歷程總數 EXtype != K  rule not like %k
                    $alllearningprocess = Exercise::query()->select('exercise.ExType', 'exercise.Rule')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', '!=', 'K')
                        ->Where('Rule', 'not like', '%K%')
                        ->count();

                    //智慧教堂應用下２個圖表
                    $chartdatas = Exercise::query()
                        ->select(DB::raw('count(year(exercise.ExTime)) as total ,year(exercise.ExTime) as year'))
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', '!=', 'K')
                        ->Where('Rule', 'not like', '%K%')
                        ->groupBy(DB::raw('year(ExTime)'))
                        ->get();

                    //個人所屬 0
                    $personAge = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '0')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();
                    //學區分享 2
                    $areaShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '2')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();
                    // 學校分享 1
                    $schoolShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '1')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();

                    // 總資源分享數
                    $overallResourece = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '0')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();


                    //题目數
                    $subjectnums = Iteminfo::query()
                        ->select(DB::raw('count(year(iteminfo.Date)) as total,year(iteminfo.Date) as year'))
                        ->join('testitem','testitem.ItemNO','iteminfo.ItemNO')
                        ->join('testpaper','testpaper.TPID','iteminfo.ItemNO')
                        ->join('member','member.MemberID','testpaper.MemberID')
                        ->where('testpaper.Status', 'E')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->whereBetween('iteminfo.Date', [$StartTime, $EndTime])
                        ->where('iteminfo.Status', 'E')
                        ->groupBy(DB::raw('year(iteminfo.Date)'))
                        ->get();

                    //試卷數
                    $examinationnums = Testpaper::query()
                        ->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                        ->join('member','member.MemberID','testpaper.MemberID')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->whereBetween('testpaper.CreateTime', [$StartTime, $EndTime])
                        ->where('testpaper.Status', 'E')
                        ->groupBy(DB::raw('year(testpaper.CreateTime)'))
                        ->get();

                    //教材數
                    $textbooknum = Teaching_resource::query()->selectRaw('count(year(created_dt)) as total ,year(created_dt) as year')
                        ->where('SchoolID', $school->SchoolID)
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->groupBy(DB::raw('year(created_dt)'))
                        ->get();

                    //線上測驗完成率
                    //分子 有做過作業的人
                    $Molecular = DB::table('course')
                        ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                        ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                        ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->where('Rule', 'not like', '%K%')
                        ->where('exscore.AnsNum', '>', '0')
                        ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
                    //分母 全部但不一定有做過作業
                    $Denominator = DB::table('course')
                        ->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                        ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                        ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->where('Rule', 'not like', '%K%')
                        ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

                    //線上測驗完成率
                    $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

                    //完成作業的人數
                    $complete = DB::table('course')
                        ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
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
                    $underways = DB::table('fc_target')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                        ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 1)
                        ->where('fc_event.public_flag', 1)
                        ->where('fc_target.active_flag', 1)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();


                    //未完成
                    $unfinished = DB::table('fc_sub_event')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                        ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 0)
                        ->where('fc_event.public_flag', 0)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();

                    //完成
                    $achieves = DB::table('fc_sub_event')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                        ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 1)
                        ->where('fc_event.public_flag', 1)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();

                    DB::table('ApiDistricts')->insert([
                        'schoolName'            => $schoolname,
                        'schoolId'              => $schoolid,
                        'teachnum'              => $teachnum,
                        'studentnum'            => $studentnum,
                        'patriarchnum'          => $patriarchnum,
                        'teachlogintable'       => ($teachlogintables == '[]') ? "[{'total':0,'year':0}]" : $teachlogintables,
                        'studylogintable'       => ($studylogintable == '[]') ? "[{'total':0,'year':0}]" : $studylogintable,
                        'curriculum'            => $curriculum,
                        'electronicalnote'      => 0,
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
                        'chartdata'             => ($chartdatas == '[]') ? "[{'total':0,'year':0}]" : $chartdatas,
                        'personAge'             => $personAge,
                        'areaShare'             => $areaShare,
                        'schoolShare'           => $schoolShare,
                        'overallResourece'      => $overallResourece,
                        'subjectnum'            => ($subjectnums == '[]') ? "[{'total':0,'year':0}]" : $subjectnums,
                        'examinationnum'        => ($examinationnums == '[]') ? "[{'total':0,'year':0}]" : $examinationnums,
                        'textbooknum'           => ($textbooknum == '[]') ? "[{'total':0,'year':0}]" : $textbooknum,
                        'underway'              => ($underways == '[]') ? "[{'total':0,'year':0}]" : $underways,
                        'unfinished'            => ($unfinished == '[]') ? "[{'total':0,'year':0}]" : $unfinished,
                        'achieve'               => ($achieves == '[]') ? "[{'total':0,'year':0}]" : $achieves,
                        'totalresources'        => null,
                        'onlineTestComplete'    => $onlinetestcomplete,
                        'productionPercentage'  => $productionpercentage,
                        'semester'              => 0,
                        'year'                  => $year,
                    ]);
                    //初始化
                    // $StartTime = [];
                    // $EndTime   = [];
                }
            }

            //排除最大字元
            ini_set('memory_limit', '-1');
        }

        return '更新完成';
    }

    /**
     * 更新所有下學期
     * 學校資訊
     */
    public function updateSemesterDown()
    {
        // 起始時間年份
        // 起始時間
        $start = DB::table('member')->selectRaw('year(RegisterTime) year')->groupBy(DB::raw('year(RegisterTime)'))->get();
        //學校ID
        $schools = SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
        foreach ($start as $item) {
            if ($item->year) {
                foreach ($schools as $school) {


                    // $StartTime[] = $item->year . '-08-01';
                    $StartTime = $item->year . '-02-01';
                    // $EndTime[] = $item->year + 1 . '-01-31';
                    $EndTime = $item->year . '-07-31';
                    $year    = $item->year;

                    //學校名稱
                    $schoolname = SchoolInfo::query()->select('SchoolName')->where('SchoolID', $school->SchoolID)->value('SchoolName');

                    //學校ID
                    $schoolid = SchoolInfo::query()->select('SchoolID')->where('SchoolID', $school->SchoolID)->value('SchoolID');

                    // 老師啟用數
                    $teachnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->select('MemberID', 'Status')
                        ->where('systemauthority.IDLevel', 'T')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //學生啟用數
                    $studentnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->where('systemauthority.IDLevel', 'S')
                        ->select('MemberID', 'Status')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //家長啟用數
                    $patriarchnum = Member::query()
                        ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                        ->where('systemauthority.IDLevel', 'P')
                        ->select('MemberID', 'Status')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('Status', '1')
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    // 老師登入數
                    $teachlogintables = DB::table('hiteach_log')
                        ->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                        ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                        ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                        ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                        ->where('IDLevel', 'T')
                        ->where('SchoolID', $school->SchoolID)
                        ->groupBy(DB::raw('year(RegisterTime)'))
                        ->get();

                    //學生登入數
                    $studylogintable = DB::table('api_a1_log')
                        ->selectRaw('count(distinct (api_a1_log.MemberID)) total, year(api_a1_log.CreateTime) year')
                        ->join('member', 'member.memberID', '=', 'api_a1_log.MemberID')
                        ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.memberID')
                        ->whereBetween('api_a1_log.CreateTime', [$StartTime, $EndTime])
                        ->where('systemauthority.IDLevel', '=', 'S')
                        ->where('member.SchoolID', '=', $school->SchoolID)
                        ->groupBy(DB::raw('year(api_a1_log.CreateTime)'))
                        ->get();

                    //課程總數
                    $curriculum = Course::query()->select('SchoolID')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->count();

                    //老師上傳影片
                    $uploadmovie =
                        CmsResource::query()->select('member_id', 'created_dt')
                            ->join('member', 'member.MemberID', 'cms_resource.member_id')
                            ->join('systemauthority', 'member.MemberID', 'systemauthority.MemberID')
                            ->where('member.SchoolID', $school->SchoolID)
                            ->where('systemauthority.IDLevel', 'T')
                            ->whereBetween('created_dt', [$StartTime, $EndTime])
                            ->count();

                    //作業作品數 (需要再確認)
                    $production = Course::query()->select('CourseNO')
                        ->join('teahomework', 'teahomework.ClassID', 'course.CourseNO')
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
                    $analogytest = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'I')
                        ->count();

                    //線上測驗 A
                    $onlinetest = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->count();

                    //班級競賽 J && K
                    $interclasscompetition = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->whereIn('ExType', ['J', 'K'])
                        ->count();

                    //HiTeach   H
                    $HiTeach = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'H')
                        ->count();

                    //成績登陸    S
                    $performancelogin = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'S')
                        ->count();

                    //合併活動 L
                    $mergeactivity = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'L')
                        ->count();

                    //網路閱卷 O
                    $onlinechecking = Exercise::query()->select('exercise.ExType')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', 'O')
                        ->count();

                    //學習歷程總數 EXtype != K  rule not like %k
                    $alllearningprocess = Exercise::query()->select('exercise.ExType', 'exercise.Rule')
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', '!=', 'K')
                        ->Where('Rule', 'not like', '%K%')
                        ->count();

                    //智慧教堂應用下２個圖表
                    $chartdatas = Exercise::query()
                        ->select(DB::raw('count(year(exercise.ExTime)) as total ,year(exercise.ExTime) as year'))
                        ->join('course', 'course.CourseNo', 'exercise.ExNO')
                        ->where('course.SchoolID', $school->SchoolID)
                        ->whereBetween('ExTime', [$StartTime, $EndTime])
                        ->where('ExType', '!=', 'K')
                        ->Where('Rule', 'not like', '%K%')
                        ->groupBy(DB::raw('year(ExTime)'))
                        ->get();

                    //個人所屬 0
                    $personAge = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '0')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();
                    //學區分享 2
                    $areaShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '2')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();
                    // 學校分享 1
                    $schoolShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '1')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();

                    // 總資源分享數
                    $overallResourece = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                        ->where('SchoolID', $school->SchoolID)
                        ->where('sharedLevel', '0')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();


                    //题目數
                    $subjectnums = Iteminfo::query()
                        ->select(DB::raw('count(year(iteminfo.Date)) as total,year(iteminfo.Date) as year'))
                        ->join('testitem','testitem.ItemNO','iteminfo.ItemNO')
                        ->join('testpaper','testpaper.TPID','iteminfo.ItemNO')
                        ->join('member','member.MemberID','testpaper.MemberID')
                        ->where('testpaper.Status', 'E')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->whereBetween('iteminfo.Date', [$StartTime, $EndTime])
                        ->where('iteminfo.Status', 'E')
                        ->groupBy(DB::raw('year(iteminfo.Date)'))
                        ->get();

                    //試卷數
                    $examinationnums = Testpaper::query()
                        ->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                        ->join('member','member.MemberID','testpaper.MemberID')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->whereBetween('testpaper.CreateTime', [$StartTime, $EndTime])
                        ->where('testpaper.Status', 'E')
                        ->groupBy(DB::raw('year(testpaper.CreateTime)'))
                        ->get();

                    //教材數
                    $textbooknum = Teaching_resource::query()->selectRaw('count(year(created_dt)) as total ,year(created_dt) as year')
                        ->where('SchoolID', $school->SchoolID)
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->groupBy(DB::raw('year(created_dt)'))
                        ->get();

                    //線上測驗完成率
                    //分子 有做過作業的人
                    $Molecular = DB::table('course')
                        ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                        ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                        ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->where('Rule', 'not like', '%K%')
                        ->where('exscore.AnsNum', '>', '0')
                        ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
                    //分母 全部但不一定有做過作業
                    $Denominator = DB::table('course')
                        ->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                        ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                        ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                        ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                        ->where('ExType', 'A')
                        ->where('Rule', 'not like', '%K%')
                        ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

                    //線上測驗完成率
                    $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

                    //完成作業的人數
                    $complete = DB::table('course')
                        ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
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
                    $underways = DB::table('fc_target')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                        ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 1)
                        ->where('fc_event.public_flag', 1)
                        ->where('fc_target.active_flag', 1)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();


                    //未完成
                    $unfinished = DB::table('fc_sub_event')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                        ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 0)
                        ->where('fc_event.public_flag', 0)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();

                    //完成
                    $achieves = DB::table('fc_sub_event')
                        ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                        ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                        ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                        ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                        ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                        ->where('SchoolID', $school->SchoolID)
                        ->where('fc_event.status', '!=', 'D')
                        ->where('fc_event.active_flag', 1)
                        ->where('fc_event.public_flag', 1)
                        ->groupBy(DB::raw('year(fc_event.create_time)'))
                        ->get();

                    DB::table('ApiDistricts')->insert([
                        'schoolName'            => $schoolname,
                        'schoolId'              => $schoolid,
                        'teachnum'              => $teachnum,
                        'studentnum'            => $studentnum,
                        'patriarchnum'          => $patriarchnum,
                        'teachlogintable'       => ($teachlogintables == '[]') ? "[{'total':0,'year':0}]" : $teachlogintables,
                        'studylogintable'       => ($studylogintable == '[]') ? "[{'total':0,'year':0}]" : $studylogintable,
                        'curriculum'            => $curriculum,
                        'electronicalnote'      => 0,
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
                        'chartdata'             => ($chartdatas == '[]') ? "[{'total':0,'year':0}]" : $chartdatas,
                        'personAge'             => $personAge,
                        'areaShare'             => $areaShare,
                        'schoolShare'           => $schoolShare,
                        'overallResourece'      => $overallResourece,
                        'subjectnum'            => ($subjectnums == '[]') ? "[{'total':0,'year':0}]" : $subjectnums,
                        'examinationnum'        => ($examinationnums == '[]') ? "[{'total':0,'year':0}]" : $examinationnums,
                        'textbooknum'           => ($textbooknum == '[]') ? "[{'total':0,'year':0}]" : $textbooknum,
                        'underway'              => ($underways == '[]') ? "[{'total':0,'year':0}]" : $underways,
                        'unfinished'            => ($unfinished == '[]') ? "[{'total':0,'year':0}]" : $unfinished,
                        'achieve'               => ($achieves == '[]') ? "[{'total':0,'year':0}]" : $achieves,
                        'totalresources'        => null,
                        'onlineTestComplete'    => $onlinetestcomplete,
                        'productionPercentage'  => $productionpercentage,
                        'semester'              => 1,
                        'year'                  => $year,
                    ]);
                    //初始化
                    // $StartTime = [];
                    // $EndTime   = [];
                }
            }
            //排除最大字元
            ini_set('memory_limit', '-1');
        }
        return '完成';
    }

    /*
     * 更新學區資料 districts_all_schools
     * 學校分類在每一個學區上
     * 0 上學期１下學期
     * */
    public function updateDistrictsUp()
    {
        // 起始時間
        $start = DB::table('member')->selectRaw('year(RegisterTime) year')
            ->whereRaw('year(RegisterTime) != 0')
            ->groupBy(DB::raw('year(RegisterTime)'))->get();

        //學區
        $schools = DB::table('district_schools')->select('district_schools.SchoolID', 'district_schools.district_id')
            ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'district_schools.SchoolID')
            ->orderBy('SchoolID', 'ASC')
            ->get();


        foreach ($start as $item) {
            foreach ($schools as $school) {

                $StartTime = $item->year . '-08-01';
                $EndTime   = $item->year + 1 . '-01-31';
                $year      = $item->year;

                //學校名稱
                $schoolname = SchoolInfo::query()->select('SchoolName')
                    ->where('SchoolID', $school->SchoolID)
                    ->value('SchoolName');

                //學校ID
                $schoolID = $school->SchoolID;
                //學區ID
                $districtID = $school->district_id;

                $teachnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->select('MemberID', 'Status')
                    ->where('systemauthority.IDLevel', 'T')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //學生啟用數
                $studentnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->where('systemauthority.IDLevel', 'S')
                    ->select('MemberID', 'Status')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //家長啟用數
                $patriarchnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->where('systemauthority.IDLevel', 'P')
                    ->select('MemberID', 'Status')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                // 老師登入數
                $teachlogintables = DB::table('hiteach_log')
                    ->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                    ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                    ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('IDLevel', 'T')
                    ->where('SchoolID', $school->SchoolID)
                    ->groupBy(DB::raw('year(RegisterTime)'))
                    ->get();

                //學生登入數
                $studylogintable = DB::table('api_a1_log')
                    ->selectRaw('count(distinct (api_a1_log.MemberID)) total, year(api_a1_log.CreateTime) year')
                    ->join('member', 'member.memberID', '=', 'api_a1_log.MemberID')
                    ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.memberID')
                    ->whereBetween('api_a1_log.CreateTime', [$StartTime, $EndTime])
                    ->where('systemauthority.IDLevel', '=', 'S')
                    ->where('member.SchoolID', '=', $school->SchoolID)
                    ->groupBy(DB::raw('year(api_a1_log.CreateTime)'))
                    ->get();

                //課程總數
                $curriculum = Course::query()->select('SchoolID')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //老師上傳影片
                $uploadmovie =
                    CmsResource::query()->select('member_id', 'created_dt')
                        ->join('member', 'member.MemberID', 'cms_resource.member_id')
                        ->join('systemauthority', 'member.MemberID', 'systemauthority.MemberID')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->where('systemauthority.IDLevel', 'T')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();

                //作業作品數 (需要再確認)
                $production = Course::query()->select('CourseNO')
                    ->join('teahomework', 'teahomework.ClassID', 'course.CourseNO')
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
                $analogytest = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'I')
                    ->count();

                //線上測驗 A
                $onlinetest = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->count();

                //班級競賽 J && K
                $interclasscompetition = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->whereIn('ExType', ['J', 'K'])
                    ->count();

                //HiTeach   H
                $HiTeach = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'H')
                    ->count();

                //成績登陸    S
                $performancelogin = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'S')
                    ->count();

                //合併活動 L
                $mergeactivity = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'L')
                    ->count();

                //網路閱卷 O
                $onlinechecking = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'O')
                    ->count();

                //學習歷程總數 EXtype != K  rule not like %k
                $alllearningprocess = Exercise::query()->select('exercise.ExType', 'exercise.Rule')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', '!=', 'K')
                    ->Where('Rule', 'not like', '%K%')
                    ->count();

                //智慧教堂應用下２個圖表
                $chartdatas = Exercise::query()
                    ->select(DB::raw('count(year(exercise.ExTime)) as total ,year(exercise.ExTime) as year'))
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', '!=', 'K')
                    ->Where('Rule', 'not like', '%K%')
                    ->groupBy(DB::raw('year(ExTime)'))
                    ->get();

                //個人所屬 0
                $personAge = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '0')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();
                //學區分享 2
                $areaShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '2')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();
                // 學校分享 1
                $schoolShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '1')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();

                // 總資源分享數
                $overallResourece = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '0')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();


                //题目數
                $subjectnums = Iteminfo::query()
                    ->select(DB::raw('count(year(iteminfo.Date)) as total,year(iteminfo.Date) as year'))
                    ->join('testitem','testitem.ItemNO','iteminfo.ItemNO')
                    ->join('testpaper','testpaper.TPID','iteminfo.ItemNO')
                    ->join('member','member.MemberID','testpaper.MemberID')
                    ->where('testpaper.Status', 'E')
                    ->where('member.SchoolID', $school->SchoolID)
                    ->whereBetween('iteminfo.Date', [$StartTime, $EndTime])
                    ->where('iteminfo.Status', 'E')
                    ->groupBy(DB::raw('year(iteminfo.Date)'))
                    ->get();

                //試卷數
                $examinationnums = Testpaper::query()
                    ->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                    ->join('member','member.MemberID','testpaper.MemberID')
                    ->where('member.SchoolID', $school->SchoolID)
                    ->whereBetween('testpaper.CreateTime', [$StartTime, $EndTime])
                    ->where('testpaper.Status', 'E')
                    ->groupBy(DB::raw('year(testpaper.CreateTime)'))
                    ->get();

                //教材數
                $textbooknum = Teaching_resource::query()->selectRaw('count(year(created_dt)) as total ,year(created_dt) as year')
                    ->where('SchoolID', $school->SchoolID)
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->groupBy(DB::raw('year(created_dt)'))
                    ->get();

                //線上測驗完成率
                //分子 有做過作業的人
                $Molecular = DB::table('course')
                    ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                    ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                    ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->where('Rule', 'not like', '%K%')
                    ->where('exscore.AnsNum', '>', '0')
                    ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
                //分母 全部但不一定有做過作業
                $Denominator = DB::table('course')
                    ->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                    ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                    ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->where('Rule', 'not like', '%K%')
                    ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

                //線上測驗完成率
                $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

                //完成作業的人數
                $complete = DB::table('course')
                    ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
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
                $underways = DB::table('fc_target')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                    ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 1)
                    ->where('fc_event.public_flag', 1)
                    ->where('fc_target.active_flag', 1)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();


                //未完成
                $unfinished = DB::table('fc_sub_event')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                    ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 0)
                    ->where('fc_event.public_flag', 0)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();

                //完成
                $achieves = DB::table('fc_sub_event')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                    ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 1)
                    ->where('fc_event.public_flag', 1)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();

                DB::table('districts_all_schools')->insert([
                    'schoolName'            => $schoolname,
                    'schoolID'              => $schoolID,
                    'districtID'            => $districtID,
                    'teachnum'              => $teachnum,
                    'studentnum'            => $studentnum,
                    'patriarchnum'          => $patriarchnum,
                    'teachlogintable'       => ($teachlogintables == '[]') ? "[{'total':0,'year':0}]" : $teachlogintables,
                    'studylogintable'       => ($studylogintable == '[]') ? "[{'total':0,'year':0}]" : $studylogintable,
                    'curriculum'            => $curriculum,
                    'electronicalnote'      => 0,
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
                    'chartdata'             => ($chartdatas == '[]') ? "[{'total':0,'year':0}]" : $chartdatas,
                    'personAge'             => $personAge,
                    'areaShare'             => $areaShare,
                    'schoolShare'           => $schoolShare,
                    'overallResourece'      => $overallResourece,
                    'subjectnum'            => ($subjectnums == '[]') ? "[{'total':0,'year':0}]" : $subjectnums,
                    'examinationnum'        => ($examinationnums == '[]') ? "[{'total':0,'year':0}]" : $examinationnums,
                    'textbooknum'           => ($textbooknum == '[]') ? "[{'total':0,'year':0}]" : $textbooknum,
                    'underway'              => ($underways == '[]') ? "[{'total':0,'year':0}]" : $underways,
                    'unfinished'            => ($unfinished == '[]') ? "[{'total':0,'year':0}]" : $unfinished,
                    'achieve'               => ($achieves == '[]') ? "[{'total':0,'year':0}]" : $achieves,
                    'totalresources'        => null,
                    'onlineTestComplete'    => $onlinetestcomplete,
                    'productionPercentage'  => $productionpercentage,
                    'semester'              => 0,
                    'year'                  => $year,
                    // ];
                ]);
                //初始化
                // $StartTime = [];
                // $EndTime   = [];

            }
            //排除最大字元
            ini_set('memory_limit', '-1');
        }
        return '更新完畢';
    }


    /*
     * 更新學區資料 districts_all_schools
     * 學校分類在每一個學區上
     * 0 上學期１下學期
     * */
    public function updateDistrictsDown()
    {
        // 起始時間
        $start = DB::table('member')->selectRaw('year(RegisterTime) year')
            ->whereRaw('year(RegisterTime) != 0')
            ->groupBy(DB::raw('year(RegisterTime)'))->get();

        //學區
        $schools = DB::table('district_schools')->select('district_schools.SchoolID', 'district_schools.district_id')
            ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'district_schools.SchoolID')
            ->orderBy('SchoolID', 'ASC')
            ->get();

        foreach ($start as $item) {
            foreach ($schools as $school) {

                $StartTime = $item->year . '-02-01';
                $EndTime   = $item->year . '-07-31';
                $year      = $item->year;

                //學校名稱
                $schoolname = SchoolInfo::query()->select('SchoolName')
                    ->where('SchoolID', $school->SchoolID)
                    ->value('SchoolName');

                //學校ID
                $schoolID = $school->SchoolID;
                //學區ID
                $districtID = $school->district_id;

                $teachnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->select('MemberID', 'Status')
                    ->where('systemauthority.IDLevel', 'T')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //學生啟用數
                $studentnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->where('systemauthority.IDLevel', 'S')
                    ->select('MemberID', 'Status')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //家長啟用數
                $patriarchnum = Member::query()
                    ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
                    ->where('systemauthority.IDLevel', 'P')
                    ->select('MemberID', 'Status')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('Status', '1')
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                // 老師登入數
                $teachlogintables = DB::table('hiteach_log')
                    ->select(DB::raw('count(year(RegisterTime)) as total,year(RegisterTime) as year'))
                    ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
                    ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
                    ->whereBetween('RegisterTime', [$StartTime, $EndTime])
                    ->where('IDLevel', 'T')
                    ->where('SchoolID', $school->SchoolID)
                    ->groupBy(DB::raw('year(RegisterTime)'))
                    ->get();

                //學生登入數
                $studylogintable = DB::table('api_a1_log')
                    ->selectRaw('count(distinct (api_a1_log.MemberID)) total, year(api_a1_log.CreateTime) year')
                    ->join('member', 'member.memberID', '=', 'api_a1_log.MemberID')
                    ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.memberID')
                    ->whereBetween('api_a1_log.CreateTime', [$StartTime, $EndTime])
                    ->where('systemauthority.IDLevel', '=', 'S')
                    ->where('member.SchoolID', '=', $school->SchoolID)
                    ->groupBy(DB::raw('year(api_a1_log.CreateTime)'))
                    ->get();

                //課程總數
                $curriculum = Course::query()->select('SchoolID')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->count();

                //老師上傳影片
                $uploadmovie =
                    CmsResource::query()->select('member_id', 'created_dt')
                        ->join('member', 'member.MemberID', 'cms_resource.member_id')
                        ->join('systemauthority', 'member.MemberID', 'systemauthority.MemberID')
                        ->where('member.SchoolID', $school->SchoolID)
                        ->where('systemauthority.IDLevel', 'T')
                        ->whereBetween('created_dt', [$StartTime, $EndTime])
                        ->count();

                //作業作品數 (需要再確認)
                $production = Course::query()->select('CourseNO')
                    ->join('teahomework', 'teahomework.ClassID', 'course.CourseNO')
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
                $analogytest = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'I')
                    ->count();

                //線上測驗 A
                $onlinetest = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->count();

                //班級競賽 J && K
                $interclasscompetition = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->whereIn('ExType', ['J', 'K'])
                    ->count();

                //HiTeach   H
                $HiTeach = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'H')
                    ->count();

                //成績登陸    S
                $performancelogin = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'S')
                    ->count();

                //合併活動 L
                $mergeactivity = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'L')
                    ->count();

                //網路閱卷 O
                $onlinechecking = Exercise::query()->select('exercise.ExType')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', 'O')
                    ->count();

                //學習歷程總數 EXtype != K  rule not like %k
                $alllearningprocess = Exercise::query()->select('exercise.ExType', 'exercise.Rule')
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', '!=', 'K')
                    ->Where('Rule', 'not like', '%K%')
                    ->count();

                //智慧教堂應用下２個圖表
                $chartdatas = Exercise::query()
                    ->select(DB::raw('count(year(exercise.ExTime)) as total ,year(exercise.ExTime) as year'))
                    ->join('course', 'course.CourseNo', 'exercise.ExNO')
                    ->where('course.SchoolID', $school->SchoolID)
                    ->whereBetween('ExTime', [$StartTime, $EndTime])
                    ->where('ExType', '!=', 'K')
                    ->Where('Rule', 'not like', '%K%')
                    ->groupBy(DB::raw('year(ExTime)'))
                    ->get();

                //個人所屬 0
                $personAge = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '0')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();
                //學區分享 2
                $areaShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '2')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();
                // 學校分享 1
                $schoolShare = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '1')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();

                // 總資源分享數
                $overallResourece = Teaching_resource::query()->select('sharedLevel', 'SchoolID')
                    ->where('SchoolID', $school->SchoolID)
                    ->where('sharedLevel', '0')
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->count();


                //题目數
                $subjectnums = Iteminfo::query()
                    ->select(DB::raw('count(year(iteminfo.Date)) as total,year(iteminfo.Date) as year'))
                    ->join('testitem','testitem.ItemNO','iteminfo.ItemNO')
                    ->join('testpaper','testpaper.TPID','iteminfo.ItemNO')
                    ->join('member','member.MemberID','testpaper.MemberID')
                    ->where('testpaper.Status', 'E')
                    ->where('member.SchoolID', $school->SchoolID)
                    ->whereBetween('iteminfo.Date', [$StartTime, $EndTime])
                    ->where('iteminfo.Status', 'E')
                    ->groupBy(DB::raw('year(iteminfo.Date)'))
                    ->get();

                //試卷數
                $examinationnums = Testpaper::query()
                    ->select(DB::raw('count(year(CreateTime)) as total,year(CreateTime) as year'))
                    ->join('member','member.MemberID','testpaper.MemberID')
                    ->where('member.SchoolID', $school->SchoolID)
                    ->whereBetween('testpaper.CreateTime', [$StartTime, $EndTime])
                    ->where('testpaper.Status', 'E')
                    ->groupBy(DB::raw('year(testpaper.CreateTime)'))
                    ->get();

                //教材數
                $textbooknum = Teaching_resource::query()->selectRaw('count(year(created_dt)) as total ,year(created_dt) as year')
                    ->where('SchoolID', $school->SchoolID)
                    ->whereBetween('created_dt', [$StartTime, $EndTime])
                    ->groupBy(DB::raw('year(created_dt)'))
                    ->get();

                //線上測驗完成率
                //分子 有做過作業的人
                $Molecular = DB::table('course')
                    ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                    ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                    ->join('exscore', 'exercise.ExNO', '=', 'exscore.ExNO')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->where('Rule', 'not like', '%K%')
                    ->where('exscore.AnsNum', '>', '0')
                    ->where('SchoolID', $school->SchoolID)->count('exscore.ExNO');
                //分母 全部但不一定有做過作業
                $Denominator = DB::table('course')
                    ->select('exercise.ExType', 'exercise.Rule', 'course.SchoolID')
                    ->join('exercise', 'course.CourseNO', '=', 'exercise.CourseNO')
                    ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
                    ->whereBetween('CourseBTime', [$StartTime, $EndTime])
                    ->where('ExType', 'A')
                    ->where('Rule', 'not like', '%K%')
                    ->where('SchoolID', $school->SchoolID)->count('major.CourseNO');

                //線上測驗完成率
                $onlinetestcomplete = ($Denominator == 0) ? 0 : intval(($Molecular / $Denominator) * 100);

                //完成作業的人數
                $complete = DB::table('course')
                    ->select('exscore.AnsNum', 'exercise.ExType', 'exercise.Rule', 'course.SchoolID')
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
                $underways = DB::table('fc_target')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
                    ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 1)
                    ->where('fc_event.public_flag', 1)
                    ->where('fc_target.active_flag', 1)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();


                //未完成
                $unfinished = DB::table('fc_sub_event')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                    ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 0)
                    ->where('fc_event.public_flag', 0)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();

                //完成
                $achieves = DB::table('fc_sub_event')
                    ->select(DB::raw('count(year(fc_event.create_time)) as total,year(fc_event.create_time) as year'))
                    ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
                    ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
                    ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
                    ->whereBetween('fc_event.create_time', [$StartTime, $EndTime])
                    ->where('SchoolID', $school->SchoolID)
                    ->where('fc_event.status', '!=', 'D')
                    ->where('fc_event.active_flag', 1)
                    ->where('fc_event.public_flag', 1)
                    ->groupBy(DB::raw('year(fc_event.create_time)'))
                    ->get();


                DB::table('districts_all_schools')->insert([

                    // $d[] = [
                    'schoolName'            => $schoolname,
                    'schoolID'              => $schoolID,
                    'districtID'            => $districtID,
                    'teachnum'              => $teachnum,
                    'studentnum'            => $studentnum,
                    'patriarchnum'          => $patriarchnum,
                    'teachlogintable'       => ($teachlogintables == '[]') ? "[{'total':0,'year':0}]" : $teachlogintables,
                    'studylogintable'       => ($studylogintable == '[]') ? "[{'total':0,'year':0}]" : $studylogintable,
                    'curriculum'            => $curriculum,
                    'electronicalnote'      => 0,
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
                    'chartdata'             => ($chartdatas == '[]') ? "[{'total':0,'year':0}]" : $chartdatas,
                    'personAge'             => $personAge,
                    'areaShare'             => $areaShare,
                    'schoolShare'           => $schoolShare,
                    'overallResourece'      => $overallResourece,
                    'subjectnum'            => ($subjectnums == '[]') ? "[{'total':0,'year':0}]" : $subjectnums,
                    'examinationnum'        => ($examinationnums == '[]') ? "[{'total':0,'year':0}]" : $examinationnums,
                    'textbooknum'           => ($textbooknum == '[]') ? "[{'total':0,'year':0}]" : $textbooknum,
                    'underway'              => ($underways == '[]') ? "[{'total':0,'year':0}]" : $underways,
                    'unfinished'            => ($unfinished == '[]') ? "[{'total':0,'year':0}]" : $unfinished,
                    'achieve'               => ($achieves == '[]') ? "[{'total':0,'year':0}]" : $achieves,
                    'totalresources'        => null,
                    'onlineTestComplete'    => $onlinetestcomplete,
                    'productionPercentage'  => $productionpercentage,
                    'semester'              => 1,
                    'year'                  => $year,
                    // ];
                ]);

                //初始化
                // $StartTime = [];
                // $EndTime   = [];

            }
            //排除最大字元
            ini_set('memory_limit', '-1');
        }
        return '更新完畢';
    }


    /*
     * 共用function array merge
     * 適用條件 多筆array
     *
     * */
    public function array_merge($datas)
    {

        $v = array();

        foreach ($datas AS $data) {
            foreach ($data as $key => $velue) {
                if (isset($ppp[$key])) {
                    $v[$key] += $value;
                } else {
                    $v[$key] = $value;
                }
            }
        }
        return $v;
    }


}
