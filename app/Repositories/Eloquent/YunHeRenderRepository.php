<?php
/**
 * Created by PhpStorm.
 * User: ares
 * Date: 2018/11/27
 * Time: 4:02 PM
 */

namespace App\Repositories\Eloquent;


use App\Models\Districts;
use App\Models\DistrictsAllSchool;
use App\Models\SchoolInfo;
use DB;
use Illuminate\Support\Collection;

class YunHeRenderRepository
{

    /**
     *  學校資訊
     *  SchoolName
     *  SchoolID
     * @param int $school
     * @return array
     */
    public function schoolInfo($school = null)
    {

        $data = SchoolInfo::query()->select('SchoolName', 'SchoolID')->where('SchoolID', $school)->get();

        return $data->toArray();
    }


    /**
     * 驗證此區域 是否存在
     * @param int $district_id
     * @return bool
     */
    public function checkDistrictExist($district_id = null)
    {
        return Districts::query()->where('district_id', $district_id)->exists();
    }

    /**
     * 檢查此區域  是否有學校存在
     * @param int $district_id
     * @param int $schoolId
     * @return bool
     */
    public function checkDistrictBySchoolExist($district_id = null, $schoolId = null)
    {
        if (isset($schoolId) && isset($district_id)) {

            return DistrictsAllSchool::query()->where('districtID', $district_id)->where('schoolID', $schoolId)->select('schoolID')->distinct()->exists();

        } elseif (isset($district_id)) {

            return DistrictsAllSchool::query()->where('districtID', $district_id)->select('schoolID')->distinct()->exists();
        }
    }


    /**
     * @param int $district_id
     * @param int $school
     * @param int $semester
     * @param int $year
     * @return \Illuminate\Support\Collection
     * @return bool
     */
    public function dashboard($district_id = null, $school = null, $semester = null, $year = null)
    {
        if (isset($district_id) && isset($semester) && isset($year)) {
            $data = DB::table('districts_all_schools')
                ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('districtID', '=', $district_id)
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->exists();
            if ($data) {
                return DB::table('districts_all_schools')
                    ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('districtID', '=', $district_id)
                    ->where('year', '!=', 0)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->get();
            }
        } elseif (isset($district_id)) {
            $data = DB::table('districts_all_schools')
                ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('districtID', '=', $district_id)
                ->where('year', '!=', 0)
                ->exists();
            if ($data) {
                return DB::table('districts_all_schools')
                    ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('districtID', '=', $district_id)
                    ->where('year', '!=', 0)
                    ->get();
            }
        } elseif (isset($school) && isset($semester) && isset($year)) {
            $data = DB::table('districts_all_schools')
                ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('schoolID', '=', $school)
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->exists();
            if ($data) {
                return DB::table('districts_all_schools')
                    ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)
                    ->where('year', '!=', 0)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->get();
            }
        } elseif (isset($school)) {
            $data = DB::table('districts_all_schools')
                ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('schoolID', '=', $school)
                ->where('year', '!=', 0)
                ->exists();
            if ($data) {
                return DB::table('districts_all_schools')
                    ->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('schoolID', '=', $school)
                    ->where('year', '!=', 0)
                    ->get();
            }
        } else {
            $data = false;
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */

        return $data;
    }

    /**
     * @param string $category
     * @param null $school_id
     * @param int $semester
     * @param int $year
     * $semester  0 上學期 ８－１  1 下學期 ２－７  2 = all
     * @return array
     */
    public function resource($category = null, $school_id = null, $semester = 2, $year = null)
    {
        switch ($semester) {
            case 0:
                $data = DB::table('teacherdata')
                    ->selectRaw("sum($category) as total,teacherdata.targetmonth date")
                    ->join('member', 'member.MemberID', 'teacherdata.memberid')
                    ->where('member.SchoolID', $school_id)
                    ->where('targetyear', $year)
                    ->whereBetween('targetmonth',[8,12])
                    ->ORwhere('targetyear', $year + 1)
                    ->where('targetmonth', 1)
                    ->where('member.SchoolID', $school_id)
                    ->groupBy('targetmonth')
                    ->get();
                $data = $this->array_json($data);

                return [
                    'num'  => array_sum($data),
                    'time' => array_keys($data),
                    'data' => array_values($data),
                ];
                break;
            case 1:
                $data = DB::table('teacherdata')
                    ->selectRaw("sum($category) as total,teacherdata.targetmonth date")
                    ->join('member', 'member.MemberID', 'teacherdata.memberid')
                    ->where('member.SchoolID', $school_id)
                    ->where('targetyear', $year)
                    ->whereBetween('targetmonth',[2,7])
                    ->where('member.SchoolID', $school_id)
                    ->groupBy('targetmonth')
                    ->get();
                $data = $this->array_json($data);

                return [
                    'num'  => array_sum($data),
                    'time' => array_keys($data),
                    'data' => array_values($data),
                ];
                break;
            default:
                $data = DB::table('teacherdata')
                    ->selectRaw("sum($category) as total,teacherdata.targetyear date")
                    ->join('member', 'member.MemberID', 'teacherdata.memberid')
                    ->where('member.SchoolID', $school_id)
                    ->where('member.SchoolID', $school_id)
                    ->groupBy('targetyear')
                    ->get();
                $data = $this->array_json($data);

                return [
                    'num'  => array_sum($data),
                    'time' => array_keys($data),
                    'data' => array_values($data),
                ];
        }

//        $data = DB::table('yu_he_districts')
//            ->selectRaw("sum($category) as total,year as date")
//            ->where('districtID', $district_id)
//            ->where('schoolId', $school_id)
//            ->groupBy('year')
//            ->get();


    }

    public function study()
    {

    }

    /**
     * @param int $district_id
     * @param int $semester
     * @param int $year
     * @param int $school_id
     * @return Collection|string
     * $semester  0 上學期 ８－１  1 下學期 ２－７  2 = all
     */

    public function getData($district_id = null, $semester = 2, $year = null, $school_id = null)
    {
        $select = '
          sum(teachnum)              teachnum,
          sum(studentnum)            studentnum,           
          sum(patriarchnum)          patriarchnum,           
          sum(teachlogintable)       teachlogintable,           
          sum(studylogintable)       studylogintable,           
          sum(curriculum)            curriculum,           
          sum(electronicalnote)      electronicalnote,           
          sum(uploadMovie)           uploadMovie,           
          sum(production)            production,           
          sum(overturnClass)         overturnClass,           
          sum(analogyTest)           analogyTest,           
          sum(onlineTest)            onlineTest,           
          sum(interClassCompetition) interClassCompetition,           
          sum(HiTeach)               HiTeach,           
          sum(performanceLogin)      performanceLogin,           
          sum(mergeActivity)         mergeActivity,           
          sum(onlineChecking)        onlineChecking,           
          sum(allLearningProcess)    allLearningProcess,
          sum(sokratesTotal)         sokratesTotal,           
          sum(personAge)             personAge,           
          sum(areaShare)             areaShare,           
          sum(schoolShare)           schoolShare,           
          sum(overallResourece)      overallResourece,           
          sum(subjectnum)            subjectnum,           
          sum(examinationnum)        examinationnum,           
          sum(textbooknum)           textbooknum,           
          sum(underway)              underway,           
          sum(unfinished)            unfinished,           
          sum(achieve)               achieve,           
          sum(molecularHomework)     molecularHomework,           
          sum(denominatorHomework)   denominatorHomework,           
          sum(denominatorOnlineTest) denominatorOnlineTest,           
          sum(molecularOnlineTest)   molecularOnlineTest ';

        switch ($semester) {
            case 1:
                if (isset($district_id) && isset($year) && isset($school_id)) {
                    return DB::table('yu_he_districts')
                        ->selectRaw($select)
                        ->where('districtID', '=', $district_id)
                        ->where('year', $year)
                        ->where('schoolId', $school_id)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('districtID', $district_id)
                        ->where('schoolId', $school_id)
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();

                } elseif (isset($district_id) && isset($year)) {
                    return DB::table('yu_he_districts')
                        ->selectRaw($select)
                        ->where('districtID', '=', $district_id)
                        ->where('year', $year)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('districtID', $district_id)
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();
                } else {
                    return '無此學區級學校數據';
                }
                break;
            case 0:
                if (isset($district_id) && isset($year) && isset($school_id)) {
                    return DB::table('yu_he_districts')
                        ->selectRaw($select)
                        ->where('year', $year)
                        ->where('districtID', '=', $district_id)
                        ->where('schoolId', $school_id)
                        ->whereBetween('month', [2, 7])
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();

                } elseif (isset($district_id) && isset($year)) {
                    return DB::table('yu_he_districts')
                        ->selectRaw($select)
                        ->where('year', $year)
                        ->where('districtID', '=', $district_id)
                        ->whereBetween('month', [2, 7])
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();
                } else {
                    return '無此學區級學校數據';
                }
                break;
            default:
                if (isset($district_id) && isset($school_id)) {
                    return DB::table('yu_he_districts')->selectRaw($select)
                        ->where('districtID', '=', $district_id)
                        ->where('schoolId', $school_id)
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();

                } elseif ($district_id) {

                    return DB::table('yu_he_districts')->selectRaw($select)
                        ->where('districtID', '=', $district_id)
                        // ->groupBy('schoolName','schoolId','schoolCode')
                        ->get();

                } else {
                    return '無此學區級學校數據';
                }
                break;
        }
    }

    /**
     * @param null $category
     * @param null $district_id
     * @param null $school_id
     * @param int $semester
     * @param null $year
     * @return array|string
     * $semester  0 上學期 ８－１  1 下學期 ２－７  2 = all
     */
    public function dataFormat($category = null, $district_id = null, $school_id = null, $semester = 2, $year = null)
    {

        switch ($semester) {
            case 0:
                if (isset($district_id) && isset($year) && isset($school_id)) {
                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,month date")
                        ->where('districtID', $district_id)
                        ->where('schoolId', $school_id)
                        ->where('year', $year)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('districtID', $district_id)
                        ->where('schoolId', $school_id)
                        ->groupBy('month')
                        ->get();

                    $data = $this->array_json($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];

                } elseif (isset($district_id) && isset($year)) {
                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,month date")
                        ->where('districtID', $district_id)
                        ->where('year', $year)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('districtID', $district_id)
                        ->groupBy('month')
                        ->get();

                    $data = $this->array_json($data);
                    // dd($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
            case 1:
                if (isset($district_id) && isset($year) && isset($school_id)) {
                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,month date")
                        ->where('districtID', $district_id)
                        ->where('schoolId', $school_id)
                        ->where('year', $year)
                        ->whereBetween('month', [2, 7])
                        ->groupBy('month')
                        ->get();

                    $data = $this->array_json($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];

                } elseif (isset($district_id) && isset($year)) {
                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,month date")
                        ->where('districtID', $district_id)
                        ->where('year', $year)
                        ->whereBetween('month', [2, 7])
                        ->groupBy('month')
                        ->get();

                    $data = $this->array_json($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
            default:
                if (isset($district_id) && isset($school_id)) {

                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,year as date")
                        ->where('districtID', $district_id)
                        ->where('schoolId', $school_id)
                        ->groupBy('year')
                        ->get();

                    $data = $this->array_json($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];

                } elseif (isset($district_id)) {
                    $data = DB::table('yu_he_districts')
                        ->selectRaw("sum($category) as total,year as date")
                        ->where('districtID', $district_id)
                        ->groupBy('year')
                        ->get();

                    $data = $this->array_json($data);

                    return [
                        'num'  => array_sum($data),
                        'time' => array_keys($data),
                        'data' => array_values($data),
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
        }


    }

    /**
     * @param $school_id
     * @param int $semester
     * @param $year
     * @return array|string
     * $semester  0 上學期 ８－１  1 下學期 ２－７  2 = all
     */
    public function schoolCourse($school_id = null, $semester = 2, $year = null)
    {
        switch ($semester) {
            case 0:
                if (isset($school_id) && isset($year)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->selectRaw('count(yu_he_course_details.courseNO) total ,yu_he_course_details.courseNO courseNO ,course.CourseName courseName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->where('yu_he_course_details.year', $year)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->groupBy('yu_he_course_details.courseNO', 'course.CourseName')
                        ->get();
                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $curriculums[]   = $item->courseName;
                            $total[]         = $item->total;
                            $curriculumsid[] = $item->courseNO;
                        }
                        return [
                            'curriculums'   => array_values($curriculums),
                            'data'          => array_values($total),
                            'curriculumsid' => array_values($curriculumsid),
                        ];
                    }
                    return [
                        'curriculums'   => 0,
                        'data'          => 0,
                        'curriculumsid' => 0,
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
            case 1:
                if (isset($school_id) && isset($year)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->selectRaw('count(yu_he_course_details.courseNO) total ,yu_he_course_details.courseNO courseNO ,course.CourseName courseName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->where('yu_he_course_details.year', $year)
                        ->whereBetween('month', [2, 7])
                        ->groupBy('yu_he_course_details.courseNO', 'course.CourseName')
                        ->get();

                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $curriculums[]   = $item->courseName;
                            $total[]         = $item->total;
                            $curriculumsid[] = $item->courseNO;
                        }
                        return [
                            'curriculums'   => array_values($curriculums),
                            'data'          => array_values($total),
                            'curriculumsid' => array_values($curriculumsid),
                        ];
                    }
                    return [
                        'curriculums'   => 0,
                        'data'          => 0,
                        'curriculumsid' => 0,
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
            default:
                if (isset($school_id)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->selectRaw('count(yu_he_course_details.courseNO) total ,yu_he_course_details.courseNO courseNO ,course.CourseName courseName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->groupBy('yu_he_course_details.courseNO', 'course.CourseName')
                        ->get();
                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $curriculums[]   = $item->courseName;
                            $total[]         = $item->total;
                            $curriculumsid[] = $item->courseNO;
                        }
                        return [
                            'curriculums'   => array_values($curriculums),
                            'data'          => array_values($total),
                            'curriculumsid' => array_values($curriculumsid),
                        ];
                    }
                    return [
                        'curriculums'   => 0,
                        'data'          => 0,
                        'curriculumsid' => 0,
                    ];

                } else {
                    return '無此學區級學校數據';
                }

        }
    }

    /**
     * @param $school_id
     * @param int $semester
     * @param $year
     * @return array|string
     * $semester  0 上學期 ８－１  1 下學期 ２－７  2 = all
     */
    public function schoolTeacher($school_id = null, $semester = 2, $year = null)
    {

        switch ($semester) {
            case 0:
                if (isset($school_id) && isset($year)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->join('member', 'member.MemberID', 'course.MemberID')
                        ->selectRaw('count(yu_he_course_details.courseNO) data,member.MemberID memberID ,member.RealName realName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->where('yu_he_course_details.year', $year)
                        ->whereBetween('month', [8, 12])
                        ->ORwhere('year', $year + 1)
                        ->where('month', 1)
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->groupBy('yu_he_course_details.courseNO', 'course.CourseName')
                        ->get();

                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $realName[] = $item->realName;
                            $total[]    = $item->data;
                            $teachId[]  = $item->memberID;
                        }
                        return [
                            'teach'    => array_values($realName),
                            'data'     => array_keys($total),
                            'teachid'  => array_values($teachId),
                            'teachnum' => array_sum($total)
                        ];
                    }
                    return [
                        'teach'    => 0,
                        'data'     => 0,
                        'teachid'  => 0,
                        'teachnum' => 0,
                    ];

                } else {
                    return '無此學區級學校數據';
                }
                break;
            case 1:
                if (isset($school_id) && isset($year)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->join('member', 'member.MemberID', 'course.MemberID')
                        ->selectRaw('count(yu_he_course_details.courseNO) data,member.MemberID memberID ,member.realName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->where('yu_he_course_details.year', $year)
                        ->whereBetween('month', [2, 7])
                        ->groupBy('member.MemberID', 'member.RealName')
                        ->get();
                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $realName[] = $item->realName;
                            $total[]    = $item->data;
                            $teachId[]  = $item->memberID;
                        }

                        return [
                            'teach'    => array_values($realName),
                            'data'     => array_keys($total),
                            'teachid'  => array_values($teachId),
                            'teachnum' => array_sum($total)
                        ];
                    }
                    return [
                        'teach'    => 0,
                        'data'     => 0,
                        'teachid'  => 0,
                        'teachnum' => 0,
                    ];
                } else {
                    return '無此學區級學校數據';
                }
                break;
            default:
                if (isset($school_id)) {
                    $data = DB::table('yu_he_course_details')
                        ->join('course', 'course.CourseNO', 'yu_he_course_details.courseNO')
                        ->join('member', 'member.MemberID', 'course.MemberID')
                        ->selectRaw('count(yu_he_course_details.courseNO) data,member.MemberID memberID ,member.RealName')
                        ->where('yu_he_course_details.schoolID', $school_id)
                        ->groupBy('member.MemberID', 'member.RealName')
                        ->get();
                    if (!count($data) == 0) {
                        foreach ($data as $item) {
                            $realName[] = $item->RealName;
                            $total[]    = $item->data;
                            $teachId[]  = $item->memberID;
                        }
                        return [
                            'teach'    => array_values($realName),
                            'data'     => array_keys($total),
                            'teachid'  => array_values($teachId),
                            'teachnum' => array_sum($total)
                        ];
                    }
                    return [
                        'teach'    => 0,
                        'data'     => 0,
                        'teachid'  => 0,
                        'teachnum' => 0
                    ];
                } else {
                    return '無此學區級學校數據';
                }

        }
    }

    /**
     * @param array $data
     * @return array
     * 格式轉化
     */
    public function array_json($data = null)
    {
        if (!count($data)) {
            return $data = [];
        }

        foreach ($data as $item) {
            if (isset($yearData[$item->date])) {
                $yearData[$item->date] += intval($item->total);
            } else {
                $yearData[$item->date] = intval($item->total);
            }
        }

        if ($yearData != null) {
            //排序
            ksort($yearData);
            //存所有學校資訊
            return $data = $yearData;
        } else {
            return $data = [];
        }
    }

    /**
     * @param array $arrayData
     * @return array
     */
    public function array_merge($arrayData)
    {

        $v = array();

        foreach ($arrayData AS $data) {
            foreach ($data as $key => $value) {
                if (isset($v[$key])) {
                    $v[$key] += $value;
                } else {
                    $v[$key] = $value;
                }
            }
        }

        return $v;
    }


}