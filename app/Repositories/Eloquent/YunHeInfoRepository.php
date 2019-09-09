<?php
/**
 * Created by PhpStorm.
 * User: ares
 * Date: 2018/11/12
 * Time: 下午2:26
 */

namespace App\Repositories\Eloquent;


use App\Models\Course;
use App\Models\Districts;
use App\Models\Exercise;
use App\Models\Iteminfo;
use App\Models\Member;
use App\Models\SchoolInfo;
use App\Models\Teaching_resource;
use App\Models\Testpaper;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;


class YunHeInfoRepository
{
    /**
     *  帳號啟用數量
     *  T 老師 Ｓ學生 Ｐ家長
     * @param int $year
     * @param int $month
     * @param int $school
     * @param string $IDLevel
     * @return int
     */
    public function memberNum($year = null, $month = null, $school = null, $IDLevel = null)
    {
        return DB::table('member')
            ->join('systemauthority', 'systemauthority.MemberID', 'member.MemberID')
            ->where('systemauthority.IDLevel', $IDLevel)
            ->whereYear('RegisterTime', '=', $year)
            ->whereMonth('RegisterTime', '=', $month)
            ->whereIn('Status', [1, 2])
            ->where('SchoolID', $school)
            ->count();

    }

    /**
     * 老師登入數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function teachLoginTables($year = null, $month = null, $school = null)
    {
        return DB::table('hiteach_log')
            ->join('member', 'hiteach_log.LOGINID', '=', 'member.LoginID')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->whereYear('RegisterTime', $year)
            ->whereMonth('RegisterTime', $month)
            ->where('IDLevel', 'T')
            ->where('SchoolID', $school)
            ->count();

    }

    /**
     *  學生登入數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function studyLoginTable($year = null, $month = null, $school = null)
    {
        return DB::table('api_a1_log')
            ->join('member', 'member.memberID', '=', 'api_a1_log.MemberID')
            ->join('systemauthority', 'systemauthority.MemberID', '=', 'member.memberID')
            ->whereYear('api_a1_log.CreateTime', $year)
            ->whereMonth('api_a1_log.CreateTime', $month)
            ->where('systemauthority.IDLevel', '=', 'S')
            ->where('member.SchoolID', '=', $school)
            ->count();
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     * 有問之後會回頭來檢查
     */
    public function curriculum($year = null, $month = null, $school = null)
    {
        //A 課程每一學期都會存在
        $A = DB::table('course')
            ->whereYear('CourseBTime', $year)
            ->where('SchoolID', $school)
            ->where('Type', '!=', 'O')
            ->orWhere('Type', 'A')
            ->where('SchoolID', $school)
            ->count();

        // Ｏ延後一學期
        $year = $year - 1;
        $O    = DB::table('course')
            ->whereYear('CourseBTime', '=', $year)
            ->whereMonth('CourseBTime', '>', 7)
            ->where('SchoolID', $school)
            ->Where('Type', 'O')
            ->count();

        $data = $A + $O;

        return $data;
    }

    /**
     * 上傳影片 數量
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     *
     */
    public function uploadMovie($year = null, $month = null, $school = null)
    {
        return DB::table('cms_material')
            ->join('exercise', 'exercise.SERIALNUMBER', 'cms_material.SERIALNUMBER')
            ->join('course', 'course.CourseNO', 'exercise.CourseNO')
            ->whereYear('exercise.ExTime', $year)
            ->whereMonth('exercise.ExTime', $month)
            ->where('course.SchoolID', $school)
            ->where('Rule', 'not like', '%K%')
            ->count();
    }


    /**
     *
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function production($year = null, $month = null, $school = null)
    {
        return Course::query()
            ->join('teahomework', 'teahomework.ClassID', 'course.CourseNO')
            ->whereYear('CourseBTime', $year)
            ->whereMonth('CourseBTime', $month)
            ->where('SchoolID', $school)
            ->count();
    }

    /**
     * 翻轉課堂數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function overturnClass($year = null, $month = null, $school = null)
    {
        return DB::table('course')
            ->join('fc_event', 'fc_event.CourseNO', '=', 'course.CourseNO')
            ->whereYear('fc_event.create_time', $year)
            ->whereMonth('fc_event.create_time', $month)
            ->where('status', '!=', 'D')
            ->where('public_flag', '1')
            ->where('active_flag', '1')
            ->where('token', '>', 0)
            ->where('SchoolID', $school)
            ->count();
    }

    /**
     * 學習歷程總數 ExType != K  rule not like %k
     * 學習歷程 系列
     * @param int $year
     * @param int $month
     * @param int $school
     * @param array $exType
     * @return int
     * 模擬測驗 I 、線上測驗 A、班級競賽 J && K、HiTeach H、成績登陸 S、合併活動 L 、網路閱卷 O
     */
    public function allLearningProcess($year = null, $month = null, $school = null, $exType = [])
    {
        return Exercise::query()
            ->join('course', 'course.CourseNo', 'exercise.CourseNo')
            ->whereYear('ExTime', $year)
            ->whereMonth('ExTime', $month)
            ->where('course.SchoolID', $school)
            ->whereIn('ExType', $exType)
            ->Where('Rule', 'not like', '%K%')
            ->count();
    }

    /**
     * $sharedLevel 參數 如下
     * 個人所屬 0、學區分享 2、學校分享 1 、總資源分享數 0
     * @param int $year
     * @param int $month
     * @param int $school
     * @param int $sharedLevel
     * @return int
     */
    public function shareResource($year, $month, $school, $sharedLevel)
    {
        return Teaching_resource::query()
            ->where('SchoolID', $school)
            ->where('sharedLevel', $sharedLevel)
            ->whereYear('created_dt', $year)
            ->whereMonth('created_dt', $month)
            ->count();
    }

    /**
     * 题目數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function subjectNum($year = null, $month = null, $school = null)
    {
        return Testpaper::query()
            ->join('testitem', 'testitem.TPID', 'testitem.TPID')
            ->join('iteminfo', 'iteminfo.ItemNO', 'testitem.ItemNO')
            ->join('member', 'member.MemberID', 'testpaper.MemberID')
            ->where('testpaper.Status', 'E')
            ->where('member.SchoolID', $school)
            ->whereYear('iteminfo.Date', $year)
            ->whereMonth('iteminfo.Date', $month)
            ->where('iteminfo.Status', 'E')
            ->count();

    }

    /**
     * 試卷數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function examinationNum($year = null, $month = null, $school = null)
    {
        return Testpaper::query()
            ->join('member', 'member.MemberID', 'testpaper.MemberID')
            ->where('member.SchoolID', $school)
            ->whereYear('testpaper.CreateTime', $year)
            ->whereMonth('testpaper.CreateTime', $month)
            ->where('testpaper.Status', 'E')
            ->count();
    }

    /**
     * 教材數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function textbookNum($year = null, $month = null, $school = null)
    {
        return Teaching_resource::query()
            ->where('SchoolID', $school)
            ->whereYear('created_dt', $year)
            ->whereMonth('created_dt', $month)
            ->count();
    }

    /**
     * 分子 作業有交件的人
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     *
     */
    public function molecularHomework($year = null, $month = null, $school = null)
    {
        return DB::table('stuhomework')
            ->join('teahomework', 'teahomework.HomeworkNO', '=', 'stuhomework.HomeworkNO')
            ->join('course', 'course.CourseNO', '=', 'teahomework.ClassID')
            ->whereYear('BeginTime', $year)
            ->whereMonth('BeginTime', $month)
            ->where('course.SchoolID', $school)
            ->count('stuhomework.MemberID');
    }

    /**
     * 分母 全部參與作業人數 不一定有交件
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     *
     */
    public function denominatorHomework($year = null, $month = null, $school = null)
    {

        return DB::table('teahomework')
            ->join('course', 'course.CourseNO', '=', 'teahomework.ClassID')
            ->join('major', 'major.CourseNO', '=', 'course.CourseNO')
            ->whereYear('BeginTime', $year)
            ->whereMonth('BeginTime', $month)
            ->where('course.SchoolID', $school)
            ->count();
    }

    /**
     * 分母 線上測驗總人數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     * A 線上評量
     */
    public function denominatorOnlineTest($year = null, $month = null, $school = null)
    {
        return DB::table('exercise')
            ->join('course', 'course.CourseNO', 'exercise.CourseNO')
            ->join('major', 'major.CourseNO', 'course.CourseNO')
            ->whereYear('ExTime', $year)
            ->whereMonth('ExTime', $month)
            ->where('SchoolID', $school)
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->count('major.MemberID');
    }

    /**
     *  分子 有做線上測驗的人數
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     * A 線上評量
     */
    public function molecularOnlineTest($year = null, $month = null, $school = null)
    {
        return DB::table('exercise')
            ->join('course', 'course.CourseNO', 'exercise.CourseNO')
            ->join('major', 'major.CourseNO', 'course.CourseNO')
            ->whereYear('ExTime', $year)
            ->whereMonth('ExTime', $month)
            ->where('SchoolID', $school)
            ->where('AnsNum', '>', 0)
            ->where('ExType', 'A')
            ->where('Rule', 'not like', '%K%')
            ->count('major.MemberID');
    }


    /**
     * 進行中
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function underWays($year = null, $month = null, $school = null)
    {
        return DB::table('fc_target')
            ->join('fc_target_student', 'fc_target_student.fc_target_id', 'fc_target.id')
            ->join('fc_event', 'fc_target.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_target_student.MemberID', 'member.MemberID')
            ->whereYear('fc_event.create_time', $year)
            ->whereMonth('fc_event.create_time', $month)
            ->where('SchoolID', $school)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->where('fc_target.active_flag', 1)
            ->count();
    }

    /**
     * 未完成
     * @param int $year
     * @param int $month
     * @param int $school
     * @return int
     */
    public function unfinished($year = null, $month = null, $school = null)
    {
        return DB::table('fc_sub_event')
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereYear('fc_event.create_time', $year)
            ->whereMonth('fc_event.create_time', $month)
            ->where('SchoolID', $school)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 0)
            ->where('fc_event.public_flag', 0)
            ->count();
    }

    /**
     * 完成
     * @param int $year `
     * @param int $month
     * @param int $school
     * @return int
     */
    public function achieves($year = null, $month = null, $school = null)
    {
        return DB::table('fc_sub_event')
            ->join('fc_student', 'fc_sub_event.id', 'fc_student.fc_sub_event_id')
            ->join('fc_event', 'fc_sub_event.fc_event_id', 'fc_event.id')
            ->join('member', 'fc_sub_event.MemberID', 'member.MemberID')
            ->whereYear('fc_event.create_time', $year)
            ->whereMonth('fc_event.create_time', $month)
            ->where('SchoolID', $school)
            ->where('fc_event.status', '!=', 'D')
            ->where('fc_event.active_flag', 1)
            ->where('fc_event.public_flag', 1)
            ->count();
    }


    /**
     *  課程細部資訊
     * @param int $year
     * @param int $month
     * @return object
     */
    public function courseDetail($year = null, $month = null)
    {

        $ExType = ['I', 'A', 'J', 'H', 'S', 'L', 'O', 'K'];
        return DB::table('exercise')
            ->selectRaw('count(ExNO) total, course.CourseNO, ExType, SchoolID, month(ExTime) month, year(ExTime) year')
            ->join('course', 'course.CourseNO', 'exercise.CourseNO')
            ->whereIn('ExType', $ExType)
            ->where('Rule', 'not like', '%K%')
            ->whereYear('ExTime', $year)
            ->whereMonth('ExTime', $month)
            ->groupBy(['CourseNO', 'ExType', 'ExTime', 'SchoolID'])
            ->get();
    }

    /**
     * @param null $year
     * @param null $month
     * @param $school_id
     * @return int
     */
    public function sokrates($year = null, $month = null, $school_id)
    {
        return DB::table('exercise')
            ->join('course', 'course.CourseNO', 'exercise.CourseNO')
            ->where('ExType', 'H')
            ->where('Rule', 'NOT LIKE', '%K%')
            ->whereYear('ExTime', $year)
            ->whereMonth('ExTime', $month)
            ->where('SchoolID', $school_id)
            ->where('tba_id', '!=', null)
            ->count('ExNO');

    }

    /**
     * 帳號起始年份
     * @return Collection
     */
    public function startTime()
    {
        return Member::query()
            ->selectRaw('year(RegisterTime) year')
            ->whereYear('RegisterTime', '>=', '2016')
            ->groupBy(DB::raw('year(RegisterTime)'))
            ->get();
    }

    /**
     * 當下年份
     */
    public function currentYear()
    {
        return Carbon::now()->format('Y');
    }

    /**
     * 全部學校排序 ASC
     *
     */
    public function schoolId()
    {
        return SchoolInfo::query()->select('SchoolID')->orderBy('SchoolID', 'ASC')->get();
    }

    /**
     * 讀取所屬區域的的學校
     * @param int $district_id
     * @return \Illuminate\Support\Collection
     */
    public function districts($district_id)
    {
        if ($district_id) {
            return DB::table('district_schools')
                ->select('district_schools.SchoolID', 'district_schools.district_id')
                ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'district_schools.SchoolID')
                ->where('district_schools.district_id', $district_id)
                ->orderBy('SchoolID', 'ASC')
                ->get();
        } else {
            return DB::table('district_schools')
                ->select('district_schools.SchoolID', 'district_schools.district_id')
                ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'district_schools.SchoolID')
                ->orderBy('SchoolID', 'ASC')
                ->get();
        }

    }

    /**
     *  學校資訊
     *  SchoolName
     *  SchoolID
     * @param int $school
     * @return array
     */
    public function schoolInfo($school = null)
    {

        $data = SchoolInfo::query()->select('SchoolName', 'SchoolID', 'Abbr')->where('SchoolID', $school)->get();

        return $data;
    }

    /**
     * 學區資訊
     * @param $district_id
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function districtInfo($district_id)
    {
        return Districts::query()->select('district_name')->where('district_id', $district_id)->value('district_name');
    }
}