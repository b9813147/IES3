<?php

namespace App\Http\Controllers\YunHeApi;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use mikehaertl\shellcommand\Command;

class RepeaterController extends Controller
{

    //public
    //middle
    public function runMiddle()
    {
        $this->learnCenterUpdateToMiddle();

        $startDate   = "2016-08-01";
        $ENDDATE     = date("Y-m-d");
        $y           = date("Y", strtotime($startDate));
        $m           = date("n", strtotime($startDate));
        $count_month = (date("Y") - $y) * 12 + (date("n") - $m) + 1;
        for ($i = 0; $i < $count_month; $i++) {
            $targetMonth = $m + 1;
            $targetYear  = $y;
            if ($targetMonth > 12) {
                $targetYear  += 1;
                $targetMonth = 1;
            }
            $targetDate = $targetYear . "-" . str_pad($targetMonth, 2, "0", STR_PAD_LEFT) . "-01";

            $this->enableAccount($y, $m, 'T');
            $this->enableAccount($y, $m, 'S');
            $this->numberOfLogin($y, $m, 'T');
            $this->numberOfLogin($y, $m, 'S');
            $this->allLearningProcess($y, $m, ['H']);
            $this->allLearningProcess($y, $m, ['O']);
            $this->allLearningProcess($y, $m, ['J', 'K']);
            $this->allLearningProcess($y, $m, ['S']);
            $this->allLearningProcess($y, $m, ['L']);
            $this->allLearningProcess($y, $m, ['HH']);
            $this->allLearningProcess($y, $m, ['A']);
            $this->allLearningProcessByTeacher($y, $m, ['H']);
            $this->allLearningProcessByTeacher($y, $m, ['O']);
            $this->allLearningProcessByTeacher($y, $m, ['J', 'K']);
            $this->allLearningProcessByTeacher($y, $m, ['S']);
            $this->allLearningProcessByTeacher($y, $m, ['L']);
            $this->allLearningProcessByTeacher($y, $m, ['HH']);
            $this->allLearningProcessByTeacher($y, $m, ['A']);
            $this->cmsByTeacher($y, $m);
            $this->courseCount($y, $m);
            $this->cms($y, $m);
//            $this->assignmentNum($y, $m);
//            $this->testingStuCount($y, $m);
//            $this->homeWorkByCount($y, $m);
//            $this->homeWorkByNum($y, $m);
//            $this->homeWorkByStuCount($y, $m);
//            $this->fcEventByCount($y, $m);
//            $this->fcEventByStuNum($y, $m);
//            $this->fcStuByCountAndProgress($y, $m);
            $this->assignmentNum($y, $m);
            $this->testingStuCount($y, $m);
            $this->homeWorkByCount($y, $m);
            $this->homeWorkByNum($y, $m);
            $this->homeWorkByStuCount($y, $m);
            $this->fcEventByCount($y, $m);
            $this->fcEventByStuNum($y, $m);
            $this->fcStuByCountAndProgress($y, $m);
            $this->testPaperByCount($y, $m);
            $this->testByItemCount($y, $m);
            $this->testItemBySchShareCount($y, $m);
            $this->testPaperSchSharedCount($y, $m);
            $this->resourceCount($y, $m);

            $m++;
            if ($m > 12) {
                $y += 1;
                $m = 1;
            }
        }
        $this->gradeName();
    }

    public function updateToFile()
    {
        $this->fileCount();
    }

    /**
     * @param int    $y
     * @param int    $m
     * @param string $IDLevel
     * @discription 逐月啟用帳號總數 身份 老師 ＝Ｔ 學生 ＝Ｓ
     */
    public function enableAccount($y, $m, $IDLevel)
    {
        $data    = [];
        $select  = 's.SchoolID, s.SchoolName, year(m.RegisterTime) as y, month(m.RegisterTime) as m, day(m.RegisterTime) as d, COUNT(m.MemberID) as dataValue';
        $groupBy = 'm.SchoolID, year(m.RegisterTime), month(m.RegisterTime),day(m.RegisterTime)';
        $data    = \DB::table('member as m')
            ->selectRaw($select)
            ->join('schoolinfo as s', 's.SchoolID', 'm.SchoolID')
            ->join('systemauthority as sys', 'sys.MemberID', 'm.MemberID')
            ->whereYear('m.RegisterTime', $y)
            ->whereMonth('m.RegisterTime', $m)
            ->where('sys.IDLevel', $IDLevel)
            ->whereIn('m.Status', [1, 2])
            ->groupBy(\DB::raw($groupBy))
            ->orderBy('m.SchoolID')
            ->get();

        // 判斷身份
        switch ($IDLevel) {
            case 'T':
                foreach ($data as $datum) {
                    \DB::connection('middle')->table('school_data')->updateOrInsert([
                        'school_id'   => $datum->SchoolID,
                        'targetYear'  => $datum->y,
                        'targetMonth' => $datum->m,
                        'targetDay'   => $datum->d,
                    ], [
                        'teacherCount' => $datum->dataValue,
                    ]);
                }
                break;
            case 'S':
                foreach ($data as $datum) {
                    \DB::connection('middle')->table('school_data')->updateOrInsert([
                        'school_id'   => $datum->SchoolID,
                        'targetYear'  => $datum->y,
                        'targetMonth' => $datum->m,
                        'targetDay'   => $datum->d,
                    ], [
                        'studentCount' => $datum->dataValue,
                    ]);
                }
                break;

        }

    }

    /**
     * @param int    $y
     * @param int    $m
     * @param string $IDLevel
     * @discription 逐月登入數 身份 老師 ＝Ｔ 學生 ＝Ｓ
     */
    public function numberOfLogin($y, $m, $IDLevel)
    {
        switch ($IDLevel) {
            case 'T':
                $data    = [];
                $select  = 'm.SchoolID, year(o.LoginTime) as y, month(o.LoginTime) as m, day(o.LoginTime) as d, COUNT(m.MemberID) as dataValue';
                $groupBy = 'm.SchoolID, year(o.LoginTime), month(o.LoginTime), day(o.LoginTime)';
                $data    = \DB::table('onlineuser as o')
                    ->selectRaw($select)
                    ->join('member as m', 'o.MemberID', 'm.MemberID')
                    ->join('systemauthority as sys', 'sys.MemberID', 'm.MemberID')
                    ->where('sys.IDLevel', $IDLevel)
                    ->whereIn('m.Status', [1, 2])
                    ->whereYear('o.LoginTime', $y)
                    ->whereMonth('o.LoginTime', $m)
                    ->groupBy(\DB::raw($groupBy))
                    ->get();
                foreach ($data as $datum) {
                    \DB::connection('middle')->table('school_data')->updateOrInsert([
                        'school_id'   => $datum->SchoolID,
                        'targetYear'  => $datum->y,
                        'targetMonth' => $datum->m,
                        'targetDay'   => $datum->d,
                    ], [
                        'teacherLoginTimes' => $datum->dataValue,
                    ]);
                }
                break;
            case 'S':
                $data    = [];
                $select  = 'm.SchoolID, year(o.CreateTime) as y, month(o.CreateTime) as m, day(o.CreateTime) as d, COUNT(m.MemberID) as dataValue';
                $groupBy = 'm.SchoolID, year(o.CreateTime), month(o.CreateTime), day(o.CreateTime)';
                $data    = \DB::table('api_a1_log as o')
                    ->selectRaw($select)
                    ->join('member as m', 'o.MemberID', 'm.MemberID')
                    ->join('systemauthority as sys', 'sys.MemberID', 'm.MemberID')
                    ->where('sys.IDLevel', $IDLevel)
                    ->whereIn('m.Status', [1, 2])
                    ->whereYear('o.CreateTime', $y)
                    ->whereMonth('o.CreateTime', $m)
                    ->groupBy(\DB::raw($groupBy))
                    ->get();
                foreach ($data as $datum) {
                    \DB::connection('middle')->table('school_data')->updateOrInsert([
                        'school_id'   => $datum->SchoolID,
                        'targetYear'  => $datum->y,
                        'targetMonth' => $datum->m,
                        'targetDay'   => $datum->d,
                    ], [
                        'studentLoginTimes' => $datum->dataValue,
                    ]);
                }
                break;
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月課程總數
     */
    public function courseCount($y, $m)
    {
        //學期資訊
        $sOrder = ($m < 1 || $m >= 8) ? 0 : 1;
        $sYear  = ($m > 7) ? $y : $y - 1;
        //取得SNO
        $data = [];
        $data = \DB::table('semesterinfo')->where([
                'AcademicYear' => $sYear,
                'SOrder'       => $sOrder
            ]
        )->get();

        $SNO     = $data[0]->SNO;
        $nextSNO = $SNO + 1;
        if ($sOrder == 1) {
            $semester_startMonth = $y . "-02-01";
            $semester_endMonth   = $y . "-08-01";
        } elseif ($m > 7) {
            $semester_startMonth = $y . "-08-01";
            $semester_endMonth   = ($y + 1) . "-02-01";
        } else {
            $semester_startMonth = ($y - 1) . "-08-01";
            $semester_endMonth   = $y . "-02-01";
        }
        $data = [];
        $data = \DB::table('course')
            ->selectRaw('SchoolID, COUNT(CourseNO) as dataValue')
            ->where('SNO', $SNO)
            ->orWhere(function ($q) use ($SNO, $semester_endMonth) {
                $q->where('Type', 'A')
                    ->where('SNO', '>', $SNO)
                    ->where('CourseBTime', '<', $semester_endMonth);
            })
            ->orWhere(function ($q) use ($SNO, $nextSNO, $semester_startMonth, $semester_endMonth) {
                $q->where('Type', 'O')
                    ->where('SNO', '=', $nextSNO)
                    ->where('CourseBTime', '>=', $semester_startMonth)
                    ->where('CourseBTime', '<', $semester_endMonth);
            })
            ->groupBy('SchoolID')
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('school_data')->updateOrInsert([
                'school_id'   => $datum->SchoolID,
                'targetYear'  => $y,
                'targetMonth' => $m,
                'targetDay'   => 0,
            ], [
                'courseCount' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param       $y
     * @param       $m
     * @param array $exType
     * @discription 逐月評量信息 模擬測驗 I 、線上測驗 A、班級競賽 J && K、HiTeach H、成績登陸 S、合併活動 L 、網路閱卷 O 、 蘇格拉底 HH=H
     */
    public function allLearningProcessByTeacher($y, $m, $exType)
    {
        $data    = [];
        $select  = 'e.MemberID, year(e.ExTime) as y, month(e.ExTime) as m, day(e.ExTime) as d, count(e.ExNO) as dataValue';
        $groupBy = 'e.MemberID, year(e.ExTime), month(e.ExTime), day(e.ExTime)';
        //判斷是否是蘇格拉底
        ($exType == ['HH'])
            ?
            $data = \DB::table('exercise as e')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'e.CourseNO')
                ->join('analysis_info as a', 'a.tba_id', 'e.tba_id')
                ->whereIn('e.ExType', ['H'])
                ->where('e.Rule', 'not like', '%K%')
                ->whereYear('e.ExTime', $y)
                ->whereMonth('e.ExTime', $m)
                ->groupBy(\DB::raw($groupBy))
                ->get()
            :
            $data = \DB::table('exercise as e')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'e.CourseNO')
                ->whereIn('e.ExType', $exType)
                ->where('e.Rule', 'not like', '%K%')
                ->whereYear('e.ExTime', $y)
                ->whereMonth('e.ExTime', $m)
                ->groupBy(\DB::raw($groupBy))
                ->get();


        switch ($exType) {
            case ['H']:
                foreach ($data as $datum) {
                    //hiTeach數量
                    $this->updateByTeacherData($datum, 'hiTeachCount');
                    //電子筆記數量
                    $this->updateByTeacherData($datum, 'enoteCount');
                }
                break;
            case ['O']:
                foreach ($data as $datum) {
                    //閱卷數量
                    $this->updateByTeacherData($datum, 'omrCount');
                }
                break;
            case ['J', 'K']:
                foreach ($data as $datum) {
                    //班際智慧服務
                    $this->updateByTeacherData($datum, 'eventCount');
                }
                break;
            case ['S']:
                foreach ($data as $datum) {
                    //成績登錄數量
                    $this->updateByTeacherData($datum, 'loginScoreCount');
                }
                break;
            case ['L']:
                foreach ($data as $datum) {
                    //合併活動
                    $this->updateByTeacherData($datum, 'combineCount');
                }
                break;
            case ['HH']:
                foreach ($data as $datum) {
                    //蘇格拉迪數量
                    $this->updateByTeacherData($datum, 'sokratesCount');
                }
                break;
            case ['A']:
                foreach ($data as $datum) {
                    //線上測驗數量
                    $this->updateByTeacherData($datum, 'assignmentCount');
                }
                break;
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：cms  cmsCount(課堂影片數量)
     */
    public function cmsByTeacher($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'e.MemberID, year(e.ExTime) as y, month(e.ExTime) as m, day(e.ExTime) as d, count(c.rid) as dataValue';
        $groupBy = 'e.MemberID, year(e.ExTime), month(e.ExTime), day(e.ExTime)';

        $data = \DB::table('exercise as e')
            ->selectRaw($select)
            ->join('cms_material as c', 'e.SERIALNUMBER', 'c.SERIALNUMBER')
            ->where('e.ExType', 'H')
            ->where('e.Rule', 'not like', '%K%')
            ->whereYear('e.ExTime', $y)
            ->whereMonth('e.ExTime', $m)
//            ->whereDay('ExTime', $d)
            ->whereIn('e.CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {

            $this->updateByTeacherData($datum, 'cmsCount');
        }
    }

    /**
     * @discription 年級：  gradeName(年級)
     */
    public function gradeName()
    {
        //學期資訊
        $select  = 'classinfo.GradeName as dataValue, course.CourseNO';
        $groupBy = 'course.CourseNO';
        $data    = \DB::table('course')
            ->selectRaw($select)
            ->join('classinfo', 'classinfo.CNO', 'course.CNO')
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByCourseDataToGradeName($datum, 'gradeName');
        }


    }


    /**
     * @param int   $y
     * @param int   $m
     * @param array $exType
     * @discription 逐月評量信息 模擬測驗 I 、線上測驗 A、班級競賽 J && K、HiTeach H、成績登陸 S、合併活動 L 、網路閱卷 O 、 蘇格拉底 HH=H
     */
    public function allLearningProcess($y, $m, $exType)
    {
        $data    = [];
        $select  = 'e.CourseNO, count(e.ExNO) as dataValue, year(e.ExTime) as y, month(e.ExTime) as m, day(e.ExTime) as d';
        $groupBy = 'e.CourseNO,year(e.ExTime), month(e.ExTime)';

        //判斷是否是蘇格拉底
        ($exType == ['HH'])
            ?
            $data = \DB::table('exercise as e')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'e.CourseNO')
                ->join('analysis_info as a', 'a.tba_id', 'e.tba_id')
                ->whereIn('e.ExType', ['H'])
                ->where('e.Rule', 'not like', '%K%')
                ->whereYear('e.ExTime', $y)
                ->whereMonth('e.ExTime', $m)
                ->groupBy(\DB::raw($groupBy))
                ->get()
            :
            $data = \DB::table('exercise as e')
                ->selectRaw($select)
                ->join('course as c', 'c.CourseNO', 'e.CourseNO')
                ->whereIn('e.ExType', $exType)
                ->where('e.Rule', 'not like', '%K%')
                ->whereYear('e.ExTime', $y)
                ->whereMonth('e.ExTime', $m)
                ->groupBy(\DB::raw($groupBy))
                ->get();

        switch ($exType) {
            case ['H']:
                foreach ($data as $datum) {
                    //hiTeach數量
                    $this->updateByCourseData($datum, 'hiTeachCount');
                    //電子筆記數量
                    $this->updateByCourseData($datum, 'enoteCount');
                }
                break;
            case ['O']:
                foreach ($data as $datum) {
                    //閱卷數量
                    $this->updateByCourseData($datum, 'omrCount');
                }
                break;
            case ['J', 'K']:
                foreach ($data as $datum) {
                    //班際智慧服務
                    $this->updateByCourseData($datum, 'eventCount');
                }
                break;
            case ['S']:
                foreach ($data as $datum) {
                    //成績登錄數量
                    $this->updateByCourseData($datum, 'loginScoreCount');
                }
                break;
            case ['L']:
                foreach ($data as $datum) {
                    //合併活動
                    $this->updateByCourseData($datum, 'combineCount');
                }
                break;
            case ['HH']:
                foreach ($data as $datum) {
                    //蘇格拉迪數量
                    $this->updateByCourseData($datum, 'sokratesCount');
                }
                break;
            case ['A']:
                foreach ($data as $datum) {
                    //線上測驗數量
                    $this->updateByCourseData($datum, 'assignmentCount');
                }
                break;
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：cms  cmsCount(課堂影片數量)
     */
    public function cms($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'e.CourseNO, count(c.rid) as dataValue, year(e.ExTime) as y, month(e.ExTime) as m, day(e.ExTime) as d';
        $groupBy = 'e.CourseNO, year(e.ExTime), month(e.ExTime), day(e.ExTime)';
        $data    = \DB::table('exercise as e')
            ->selectRaw($select)
            ->join('cms_material as c', 'e.SERIALNUMBER', 'c.SERIALNUMBER')
            ->where('e.ExType', 'H')
            ->where('e.Rule', 'not like', '%K%')
            ->whereYear('e.ExTime', $y)
            ->whereMonth('e.ExTime', $m)
//            ->whereDay('e.ExTime', $d)
            ->whereIn('e.CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {

            $this->updateByCourseData($datum, 'cmsCount');
        }
    }


    /**
     * @param int $y
     * @param int $m
     * @discription  逐月評量信息：assignment  testingStuNum (總學生人次)
     */
    public function assignmentNum($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'e.CourseNO,count(m.MemberID) as dataValue';
        $groupBy = 'e.CourseNO';

        $data = \DB::table('exercise as e')
            ->selectRaw($select)
            ->join('major as m', 'e.CourseNO', 'm.CourseNO')
            ->where('e.ExType', 'H')
            ->where('e.Rule', 'not like', '%K%')
            ->whereYear('e.ExTime', $y)
            ->whereMonth('e.ExTime', $m)
//            ->whereDay('e.ExTime', $d)
            ->whereIn('e.CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'testingStuNum' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription  逐月評量信息：assignment  testingStuCount (完成人次)
     */
    public function testingStuCount($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'e.CourseNO,count(m.MemberID) as dataValue';
        $groupBy = 'e.CourseNO';

        $data = \DB::table('exercise as e')
            ->selectRaw($select)
            ->join('exscore as s', 'e.ExNO', 's.ExNO')
            ->join('member as m', 'm.MemberID', 'e.MemberID')
            ->where('e.ExType', 'A')
            ->where('e.Rule', 'not like', '%K%')
            ->whereYear('e.ExTime', $y)
            ->whereMonth('e.ExTime', $m)
//            ->whereDay('e.ExTime', $d)
            ->where('s.AnsNum', '>', 0)
            ->whereIn('m.Status', ['1,2'])
            ->whereIn('e.CourseNO', $query)
            ->groupBy($groupBy)
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'testingStuCount' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：homework  homeworkCount(線上作業)
     */
    public function homeWorkByCount($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 't.ClassID as CourseNO, count(t.HomeworkNO) as dataValue';
        $groupBy = 't.ClassID';

        $data = \DB::table('teahomework as t')
            ->selectRaw($select)
            ->whereYear('t.BeginTime', $y)
            ->whereMonth('t.BeginTime', $m)
            ->whereIn('t.ClassID', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'homeworkCount' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：homework homeworkStuNum (總學生人次)
     */
    public function homeWorkByNum($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 't.ClassID as CourseNO ,count(m.MemberID) as dataValue';
        $groupBy = 't.ClassID';

        $data = \DB::table('teahomework as t')
            ->selectRaw($select)
            ->join('major as m', 't.ClassID', 'm.CourseNO')
            ->whereYear('t.BeginTime', $y)
            ->whereMonth('t.BeginTime', $m)
//            ->whereDay('t.BeginTime', $d)
            ->whereIn('t.ClassID', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'homeworkStuNum' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：homework homeworkStuCount (完成人次)
     */
    public function homeWorkByStuCount($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 't.ClassID as CourseNO ,count(t.HomeworkNO) as dataValue';
        $groupBy = 't.ClassID';

        $data = \DB::table('teahomework as t')
            ->selectRaw($select)
            ->join('stuhomework as s', 't.HomeworkNO', 's.HomeworkNO')
            ->join('member as m', 's.MemberID', 'm.MemberID')
            ->whereYear('t.BeginTime', $y)
            ->whereMonth('t.BeginTime', $m)
//            ->whereDay('t.BeginTime', $d)
            ->whereIn('m.Status', [1, 2])
            ->where('s.Status', 1)
            ->whereIn('t.ClassID', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'homeworkStuCount' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：fcEventCount (自學任務)
     */
    public function fcEventByCount($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'f.CourseNO, count(f.id) as dataValue';
        $groupBy = 'f.CourseNO';

        $data = \DB::table('fc_event as f')
            ->selectRaw($select)
            ->whereYear('f.create_time', $y)
            ->whereMonth('f.create_time', $m)
//            ->whereDay('f.create_time', $d)
            ->where('f.token', '>', 0)
            ->where('f.public_flag', 1)
            ->where('f.active_flag', 1)
            ->where('f.status', '!=', 'D')
            ->whereIn('f.CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'fcEventCount' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：fcEventStuNum (自學任務學生總數)
     */
    public function fcEventByStuNum($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'f.CourseNO, count(f.id) as dataValue';
        $groupBy = 'f.CourseNO';

        $data = \DB::table('fc_event as f')
            ->selectRaw($select)
            ->join('major as m', 'f.CourseNO', 'm.CourseNO')
            ->whereYear('f.create_time', $y)
            ->whereMonth('f.create_time', $m)
//            ->whereDay('f.create_time', $d)
            ->where('f.token', '>', 0)
            ->where('f.public_flag', 1)
            ->where('f.active_flag', 1)
            ->where('f.status', '!=', 'D')
            ->whereIn('f.CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            \DB::connection('middle')->table('course_data')->updateOrInsert([
                'course_no'   => $datum->CourseNO,
                'targetYear'  => $y,
                'targetMonth' => $m,
            ], [
                'fcEventStuNum' => $datum->dataValue,
            ]);
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription 逐月評量信息：  fcEventStuCount (學生完成數) fcEventStuInProgress (學生進行中) (自學任務完成情況)
     */
    public function fcStuByCountAndProgress($y, $m)
    {
        $query   = $this->semesterQuery($y, $m);
        $data    = [];
        $select  = 'CourseNO, id';
        $groupBy = 'CourseNO';

        $data = \DB::table('fc_event')
            ->selectRaw($select)
            ->whereYear('create_time', $y)
            ->whereMonth('create_time', $m)
//            ->whereDay('create_time', $d)
            ->where('token', '>', 0)
            ->where('public_flag', '=', 1)
            ->where('active_flag', '=', 1)
            ->where('status', '!=', 'D')
            ->whereIn('CourseNO', $query)
            ->groupBy(\DB::raw($groupBy))
            ->orderBy('CourseNO', 'ASC')
            ->get();

        $fcStatus       = [];
        $fcStatus_index = 0;
        foreach ($data as $val) {
            $fcStatus[$fcStatus_index]['CourseNO']        = $val->CourseNO;
            $fcStatus[$fcStatus_index]['finishCount']     = 0;
            $fcStatus[$fcStatus_index]['inProgressCount'] = 0;
            //$fcStatus[$fcStatus_index]['nonstartcount'] = 0;

            //每位學生該完成的數量
            $fc_sub_event_count = [];
            $fc_sub_event_count = $this->fcSubEventByCount($val);
            $fc_sub_event_count = (count($fc_sub_event_count) > 0) ? $fc_sub_event_count[0]->fcSubEventCount : 0;

            $fc_target_count = [];
            $fc_target_count = $this->fcTargetByCount($val);

            $fc_target_count = (count($fc_target_count) > 0) ? $fc_target_count[0]->fcTargetCount : 0;
            $fc_event_num    = $fc_sub_event_count + $fc_target_count;

            //每位學生完成的數量
            $stu_sub_event_count = [];
            $stu_sub_event_count = $this->stuSubEventCount($val);
            $count_stu_sub_event = COUNT($stu_sub_event_count);
            $temp                = [];
            for ($index = 0; $index < $count_stu_sub_event; $index++) {
                $temp[] = ['MemberID' => $stu_sub_event_count[$index]->MemberID, 'fc_event_count' => $stu_sub_event_count[$index]->sub_event_count];
            }

            $stu_target_count       = $this->stuTargetCount($val);
            $count_stu_target_count = COUNT($stu_target_count);
            $temp_index             = 0;

            for ($index = 0; $index < $count_stu_target_count; $index++) {
                while ($temp_index < $count_stu_sub_event && $temp[$temp_index]['MemberID'] < $stu_target_count[$index]->MemberID) {
                    $temp_index++;
                }

                if ($temp_index < $count_stu_sub_event) {
                    if ($temp[$temp_index]['MemberID'] == $stu_target_count[$index]->MemberID) {
                        $temp[$temp_index]['fc_event_count'] += $stu_target_count[$index]->target_count;
                        $temp_index++;
                    } else {
                        $temp[] = ['MemberID' => $stu_target_count[$index]->MemberID, 'fc_event_count' => $stu_target_count[$index]->target_count];
                    }
                } else {
                    $temp[] = ['MemberID' => $stu_target_count[$index]->MemberID, 'fc_event_count' => $stu_target_count[$index]->target_count];
                }
            }

            foreach ($temp as $tp) {
                if ($tp['fc_event_count'] == $fc_event_num) {
                    $fcStatus[$fcStatus_index]['finishCount']++;
                } elseif ($tp['fc_event_count'] > 0) {
                    $fcStatus[$fcStatus_index]['inProgressCount']++;
                }
            }

            $fcStatus_index++;
        }

        foreach ($fcStatus as $datum) {
            for ($d = 1; $d <= 31; $d++) {
                \DB::connection('middle')->table('course_data')->updateOrInsert([
                    'course_no'   => $datum['CourseNO'],
                    'targetYear'  => $y,
                    'targetMonth' => $m,
                ], [
                    'fcEventStuCount'      => $datum['finishCount'],
                    'fcEventStuInProgress' => $datum['inProgressCount']
                ]);
            }

        }

    }

    /**
     * @param int $y
     * @param int $m
     * @discription 試卷數量: testPaperCount (老師個人試卷)
     */
    public function testPaperByCount($y, $m)
    {
        $data    = [];
        $select  = 't.MemberID,count(t.TPID) as dataValue, year(t.UpdateTime) as y, month(t.UpdateTime) as m, day(t.UpdateTime) as d';
        $groupBy = 't.MemberID, year(t.UpdateTime), month(t.UpdateTime), day(t.UpdateTime)';

        $data = \DB::table('testpaper as t')
            ->selectRaw($select)
//            ->join('member as m', 't.MemberID', 'm.MemberID')
//            ->whereIn('m.Status', [1, 2])
            ->whereYear('t.UpdateTime', $y)
            ->whereMonth('t.UpdateTime', $m)
//            ->whereDay('t.UpdateTime', $d)
            ->where('t.Status', 'E')
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByTeacherData($datum, 'testPaperCount');
        }

    }

    /**
     * @param int $y
     * @param int $m
     * @discription 老師個人試題: testItemCount (試題數量)
     */
    public function testByItemCount($y, $m)
    {
        $data    = [];
        $select  = 't.MemberID,count(i.ItemNO) as dataValue, year(t.UpdateTime) as y, month(t.UpdateTime) as m, day(t.UpdateTime) as d';
        $groupBy = 't.MemberID, year(t.UpdateTime), month(t.UpdateTime), day(t.UpdateTime)';

        $data = \DB::table('testpaper as t')
            ->selectRaw($select)
//            ->join('member as m', 't.MemberID', 'm.MemberID')
            ->join('testitem as a', 't.TPID', 'a.TPID')
            ->join('iteminfo as i', 'a.ItemNO', 'i.ItemNO')
//            ->whereIn('m.Status', [1, 2])
            ->whereYear('t.UpdateTime', $y)
            ->whereMonth('t.UpdateTime', $m)
//            ->whereDay('t.UpdateTime', $d)
            ->where('t.Status', 'E')
            ->where('i.Status', 'E')
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByTeacherData($datum, 'testItemCount');
        }

    }

    /**
     * @param int $y
     * @param int $m
     * @discription 老師個人試題(校分享): testItemSchSharedCount (試題數量)
     */
    public function testItemBySchShareCount($y, $m)
    {
        $data    = [];
        $select  = 't.MemberID,count(i.ItemNO) as dataValue, year(t.CreateTime) as y, month(t.CreateTime) as m, day(t.CreateTime) as d';
        $groupBy = 't.MemberID, year(t.CreateTime), month(t.CreateTime), day(t.CreateTime)';

        $data = \DB::connection('public')->table('testpaperinfo as t')
            ->selectRaw($select)
//            ->join('member as m', 't.MemberID', 'm.MemberID')
            ->join('testitem as a', 'a.TestpaperID', 't.TestpaperID')
            ->join('iteminfo as i', 'a.ItemNO', 'i.ItemNO')
//            ->whereIn('m.Status', [1, 2])
            ->whereYear('t.CreateTime', $y)
            ->whereMonth('t.CreateTime', $m)
//            ->whereDay('t.CreateTime', $d)
            ->where('t.Status', 'E')
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByTeacherData($datum, 'testItemSchSharedCount');
        }

    }

    /**
     * @param int $y
     * @param int $m
     * @discription  老師個人試卷(校分享) testPaperSchSharedCount (學校測驗分享數量)
     */
    public function testPaperSchSharedCount($y, $m)
    {
        $data    = [];
        $select  = 't.MemberID,year(t.CreateTime) as y,month(t.CreateTime)as m,day(t.CreateTime) as d';
        $groupBy = 't.MemberID, year(t.CreateTime),month(t.CreateTime),day(t.CreateTime)';

        $data = \DB::connection('public')
            ->table('testpaperinfo as t')
            ->selectRaw($select)
//            ->join('member as m', 't.MemberID', 'm.MemberID')
//            ->whereIn('m.Status', [1, 2])
            ->whereYear('t.CreateTime', $y)
            ->whereYear('t.CreateTime', $m)
            ->where('t.Status', 'E')
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByTeacherData($datum, 'testPaperSchSharedCount');
        }
    }

    /**
     * @param int $y
     * @param int $m
     * @discription   老師個人教材(resourceCount)
     */
    public function resourceCount($y, $m)
    {
        $data    = [];
        $select  = 'm.MemberID,f.fileCount as dataValue, year(f.date) as y,month(f.date)as m,day(f.date) as d';
        $groupBy = 'm.MemberID, year(f.date), month(f.date), day(f.date)';

        $data = \DB::connection('middle')->table('member as m')
            ->selectRaw($select)
            ->join('file_data as f', 'f.memberID', 'm.MemberID')
            ->whereYear('f.date', $y)
            ->whereMonth('f.date', $m)
//            ->whereDay('f.date', $d)
            ->groupBy(\DB::raw($groupBy))
            ->get();

        foreach ($data as $datum) {
            $this->updateByTeacherData($datum, 'resourceCount');
        }

    }

    /**
     * @disscription 記錄所有使用者檔案數,清除file_data table 並重建
     */
    private function fileCount()
    {
        \DB::connection('middle')->table('file_data')->truncate();

        $target = storage_path('app/ies2/Field/');
        // Basic example
        $dirs     = array();
        $command1 = new Command("find " . $target . " -maxdepth 1 -mindepth 1 -type d");
        if ($command1->execute()) {
            $result = $command1->getOutput();
            $dirs   = explode("\n", $result);
//            var_dump($dirs);
        } else {
            echo $command1->getError();
            $exitCode = $command1->getExitCode();
        }

        foreach ($dirs as $dir) {
            $command = new Command("find " . $dir . " -not -path '*/\.*' -type f \( ! -iname '.*' \) -printf '%TY-%Tm-%Td\n' | sort | uniq -c");
            if ($command->execute()) {
                $result = $command->getOutput();
                if (!$result == '') {
                    $pieces = explode("\n", $result);
                    array_walk($pieces, function (&$item, $index) {
                        $i    = trim($item);
                        $item = explode(" ", $i);
                    });

                    foreach ($pieces as $piece) {
                        $member      = explode("/", $dir);
                        $member      = end($member);
                        $file        = $piece[0];
                        $date        = $piece[1];
                        $checkMember = $this->checkMember($member, $date);
                        ($checkMember) ? $this->fileUpdate($member, $file, $date) : $this->fileCreate($member, $file, $date);
                    }
                }
            }
        }


    }

    /**
     * @param Collection $datum
     * @param string     $field
     * @discriptoion  欄位資訊 $Filed = sokratesCount、enoteCount、cmsCount、hiTeachCount、omrCount、
     * eventCount、loginScoreCount、combineCount、assignmentCount、testingStuNum、
     * testingStuCount、homeworkCount、homeworkStuNum、homeworkStuCount、
     * fcEventCount、fcEventStuNum、fcEventStuCount、fcEventStuInProgress
     */
    private function updateByCourseData($datum, $field)
    {

        \DB::connection('middle')->table('course_data')->updateOrInsert([
            'course_no'   => $datum->CourseNO,
            'targetYear'  => $datum->y,
            'targetMonth' => $datum->m,
        ], [
            "$field" => $datum->dataValue,
        ]);
    }

    /**
     * @param Collection $datum
     * @param string     $field
     * @discriptoion  欄位資訊 $Filed = resourceCount、resourceSchsharedCount、
     * resourceDissharedCount、testPaperCount、testPaperSchsharedCount、
     * testPaperDissharedCount、testItemCount、testItemSchsharedCount、
     * testItemDissharedCount
     */
    private function updateByTeacherData($datum, $field)
    {
        \DB::connection('middle')->table('teacher_data')->updateOrInsert([
            'member_id'   => $datum->MemberID,
            'targetYear'  => $datum->y,
            'targetMonth' => $datum->m,
            'targetDay'   => $datum->d,
        ], [
            "$field" => $datum->dataValue,
        ]);

    }

    /**
     * @param Collection $datum
     * @return array|\Illuminate\Support\Collection
     * @discription 檢查是Table避免重複新增
     */
//    private function recordByTeacherData($datum)
//    {
//        $record = [];
//        $record = \DB::connection('middle')->table('teacher_data')
//            ->select('member_id', 'targetYear', 'targetMonth', 'targetDay')
//            ->where([
//                    'member_id'   => $datum->MemberID,
//                    'targetYear'  => $datum->y,
//                    'targetMonth' => $datum->m,
//                    'targetDay'   => $datum->d,
//                ]
//            )->get();
//        return $record;
//    }

    /**
     * @param Collection $datum
     * @return array|\Illuminate\Support\Collection
     * @discription 檢查是Table避免重複新增
     */
//    private function recordByCourseData($datum)
//    {
//        $record = [];
//        $record = \DB::connection('middle')->table('course_data')
//            ->select('course_no', 'targetYear', 'targetMonth', 'targetDay')
//            ->where([
//                    'course_no'   => $datum->CourseNO,
//                    'targetYear'  => $datum->y,
//                    'targetMonth' => $datum->m,
//                    'targetDay'   => $datum->d,
//                ]
//            )->get();
//        return $record;
//    }

    /**
     * @param int $y
     * @param int $m
     * @return \Illuminate\Database\Query\Builder
     */
    private function semesterQuery($y, $m)
    {
        $sOrder = ($m < 1 || $m >= 8) ? 0 : 1;
        $sYear  = ($m > 7) ? $y : $y - 1;

        //取得SNO
        $data = [];
        $data = \DB::table('semesterinfo')->where([
                'AcademicYear' => $sYear,
                'SOrder'       => $sOrder
            ]
        )->get();

        $SNO     = $data[0]->SNO;
        $nextSNO = $SNO + 1;
        if ($sOrder == 1) {
            $semester_startMonth = $y . "-02-01";
            $semester_endMonth   = $y . "-08-01";
        } elseif ($m > 7) {
            $semester_startMonth = $y . "-08-01";
            $semester_endMonth   = ($y + 1) . "-02-01";
        } else {
            $semester_startMonth = ($y - 1) . "-08-01";
            $semester_endMonth   = $y . "-02-01";
        }

        $query = \DB::table('course')
            ->select('CourseNO')
            ->where('SNO', $SNO)
            ->orWhere(function ($q) use ($SNO, $semester_endMonth) {
                $q->where('Type', 'A')
                    ->where('SNO', '>', $SNO)
                    ->where('CourseBTime', '<', $semester_endMonth);
            })
            ->orWhere(function ($q) use ($SNO, $nextSNO, $semester_startMonth, $semester_endMonth) {
                $q->where('Type', 'O')
                    ->where('SNO', '=', $nextSNO)
                    ->where('CourseBTime', '>=', $semester_startMonth)
                    ->where('CourseBTime', '<', $semester_endMonth);
            });

        return $query;
    }

    /**
     * @param $datum
     * @return array|\Illuminate\Support\Collection
     */
//    private function recordBySchool($datum)
//    {
//        $record = [];
//        $record = \DB::connection('middle')->table('school_data')->select('*')->where([
//                'school_id'   => $datum->SchoolID,
//                'targetYear'  => $datum->y,
//                'targetMonth' => $datum->m,
//                'targetDay'   => $datum->d,
//            ]
//        )->get();
//        return $record;
//    }

    /**
     * @param $val
     * @return mixed
     */
    private function fcSubEventByCount($val)
    {
        return \DB::table('fc_sub_event')
            ->selectRaw('count(id) as fcSubEventCount')
            ->where('fc_event_id', $val->id)
            ->get();
    }

    /**
     * @param $val
     * @return mixed
     */
    private function fcTargetByCount($val)
    {
        return \DB::table('fc_target')->selectRaw('count(id) as fcTargetCount')
            ->where('fc_event_id', $val->id)
            ->where('active_flag', 1)
            ->get();

    }

    /**
     * @param $val
     * @return mixed
     */
    private function stuSubEventCount($val)
    {
        return \DB::table('fc_event as f')
            ->selectRaw('s.MemberID, count(s.id) as sub_event_count')
            ->join('fc_sub_event as t', 'f.id', 't.fc_event_id')
            ->join('fc_student as s', 't.id', 's.fc_sub_event_id')
            ->join('major as a', 'f.CourseNO', 'a.CourseNO')
            ->join('member as m', 'a.MemberID', 'm.MemberID')
            ->where('f.id', $val->id)
            ->whereIn('m.Status', [1, 2])
            ->where('s.read_flag', 1)
            ->groupBy('s.MemberID')
            ->orderBy('s.MemberID')
            ->get();

    }

    /**
     * @param $val
     * @return mixed
     */
    private function stuTargetCount($val)
    {
        return \DB::table('fc_event as f')
            ->selectRaw('s.MemberID, count(s.id) as target_count')
            ->join('fc_target as t', 'f.id', 't.fc_event_id')
            ->join('fc_target_student as s', 't.id', 's.fc_target_id')
            ->join('member as m', 's.MemberID', 'm.MemberID')
            ->where('f.id', $val->id)
            ->whereIn('m.Status', [1, 2])
            ->where('s.finish_flag', 1)
            ->groupBy('s.MemberID')
            ->orderBy('s.MemberID')
            ->get();
    }

    /**
     * @param $member
     * @param $file
     * @param $date
     * @return int
     */
    private function fileUpdate($member, $file, $date)
    {
        return \DB::connection('middle')->table('file_data')
            ->where([
                'memberID' => $member,
                'date'     => $date
            ])
            ->update([
                'fileCount' => $file,
            ]);
    }

    /**
     * @param $member
     * @param $file
     * @param $date
     * @return bool
     */
    private function fileCreate($member, $file, $date)
    {
        return \DB::connection('middle')->table('file_data')
            ->insert([
                'memberID'  => $member,
                'fileCount' => $file,
                'date'      => $date,
            ]);
    }

    /**
     * @param $member
     * @param $date
     * @return bool
     */
    private function checkMember($member, $date)
    {
        $checkMember = \DB::connection('middle')->table('file_data')
            ->select('memberID', 'date')
            ->where('memberID', $member)
            ->where('date', $date)
            ->exists();
        return $checkMember;
    }

    /**
     * @disscription 清除中繼DB（middle）所有table 並重寫數據
     */
    private function learnCenterUpdateToMiddle()
    {
        \DB::connection('middle')->table('member')->truncate();
        \DB::connection('middle')->table('course')->truncate();
        \DB::connection('middle')->table('course_data')->truncate();
        \DB::connection('middle')->table('school_data')->truncate();
        \DB::connection('middle')->table('teacher_data')->truncate();

        $members = \DB::table('member')
            ->select('member.MemberID', 'member.district_id', 'member.SchoolID', 'member.LoginID', 'member.NickName', 'member.RealName', 'member.RegisterTime', 'member.LoginTimes', 'member.Status')
            ->join('systemauthority', 'member.MemberID', 'systemauthority.MemberID')
            ->where('systemauthority.IDLevel', 'T')
            ->whereIn('member.Status', [1, 2])
            ->get();

        foreach ($members as $member) {
            \DB::connection('middle')
                ->table('member')
                ->insert((array)$member);
        }
        $courses = \DB::table('course')
            ->select('CourseNO', 'MemberID', 'ClassID', 'CNO', 'SNO', 'SchoolID', 'CourseCode', 'CourseName', 'departmentcode', 'CourseCount', 'CourseBTime', 'SubjectID', 'Type')
            ->get();

        foreach ($courses as $course) {
            \DB::connection('middle')
                ->table('course')
                ->insert((array)$course);
        }
    }

    /**
     * @param $datum
     * @param $field
     */
    private function updateByCourseDataToGradeName($datum, $field)
    {
        \DB::connection('middle')->table('course_data')
            ->where('course_no', $datum->CourseNO)
            ->update([$field => $datum->dataValue]);
    }
}
