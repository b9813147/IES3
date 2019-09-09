<?php

namespace App\Repositories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Repositories\Base\BaseRepository;

class CourseRepository extends BaseRepository
{
    protected $model;

    public function __construct(Course $course)
    {
        $this->model = $course;
    }


    /**
     * 查詢老師的任課課程
     *
     * @param integer $memberID
     * @param integer|array $sno
     *
     * @return mixed
     *
     */
    public function findForMasterTeacher($memberID, $sno)
    {
        $model = $this->model->query()->select('course.*', 'semesterinfo.*')
            ->join('semesterinfo', 'course.SNO', '=', 'semesterinfo.SNO')
            ->when(is_array($sno), function ($query) use ($sno) {
                return $query->whereIn('course.SNO', $sno);
            }, function ($query) use ($sno) {
                return $query->where('course.SNO', $sno);
            })
            ->where('course.MemberID', $memberID)
            ->orderBy('semesterinfo.AcademicYear', 'desc')
            ->orderBy('semesterinfo.SOrder', 'desc')
            ->orderBy('course.CourseName', 'asc')
            ->get();

        return $model;
    }

    /**
     * 查詢老師的協同課程
     *
     * @param integer $memberID
     * @param integer $sno
     *
     * @return mixed
     *
     */
    public function findForSubjectTeacher($memberID, $sno)
    {
        $model = $this->model->query()->select('course.*', 'semesterinfo.*')
            ->join('classpower', 'course.CourseNO', '=', 'classpower.ClassID')
            ->join('semesterinfo', 'course.SNO', '=', 'semesterinfo.SNO')
            ->when(is_array($sno), function ($query) use ($sno) {
                return $query->whereIn('course.SNO', $sno);
            }, function ($query) use ($sno) {
                return $query->where('course.SNO', $sno);
            })
            ->where('classpower.MemberID', $memberID)
            ->where('course.MemberID', '!=', $memberID)
            ->orderBy('semesterinfo.AcademicYear', 'desc')
            ->orderBy('semesterinfo.SOrder', 'desc')
            ->orderBy('course.CourseName', 'asc')
            ->get();

        return $model;
    }


    /**
     * 建立課程
     *
     * @param $request
     * @param $user
     * @return Builder|Model
     */
    public function createCourseByTeacher($request, $user)
    {
        $course = $this->model->query()->create([
            'CourseNO'    => $this->model->query()->select('CourseNO')->max('CourseNO') + 1,
            'MemberID'    => $user->MemberID,
            'CNO'         => $request->CNO,
            'SNO'         => $request->SNO,
            'SchoolID'    => $user->SchoolID,
            'CourseCode'  => $this->getUniqueCourseCode($request->CourseCode),
            'CourseName'  => $request->CourseName,
            'Subject'     => $request->Subject,
            'majorcode'   => $this->getUniqueMajorCode($request->majorcode),
            'validdt'     => $request->validdt,
            'Type'        => $request->Type ?: null,
            'tmcourse'    => $request->tmcourse ?: 0,
            'manageby'    => $request->manageby ?: 'T',
            'CourseBTime' => Carbon::now()->format('Y-m-d H:m:s'),
        ]);

        return $course;
    }

    /**
     * 更新課程邀請碼
     *
     * @param $request
     * @param $courseNO
     * @return bool
     */
    public function updateMajorCodeByCourse($request, $courseNO)
    {
        $course = $this->model->query()
            ->findOrFail($courseNO, ['CourseNO'])
            ->update([
                'majorcode' => $request->majorcode ?: $this->getUniqueMajorCode(),
                'validdt'   => $request->validdt,
            ]);

        return $course;
    }

    /**
     * 取得課程資訊
     *
     * @param integer $courseNO
     * @return mixed
     */
    public function getCourses($courseNO)
    {
        return $this->findBy('CourseNO', $courseNO);
    }

    /**
     * 編輯課程
     *
     * @param $request
     * @param $courseNO
     * @return bool
     */
    public function editByCourse($request, $courseNO)
    {
        $course = $this->model->query()->findOrFail($courseNO, ['CourseNO']);

        return $course->update($request);
    }

    /**
     * 隨機產生唯一的CourseCode
     *
     * @param string $CourseCode
     * @return string
     */
    private function getUniqueCourseCode($CourseCode = null): string
    {
        $CourseCode ?: $CourseCode =substr(rand(), 0, 8);

        for ($i = 1; $i > 0; $i++) {
            if (!$this->model->query()->where('CourseCode', $CourseCode)->exists()) {
                return $CourseCode;
            }
            $CourseCode = substr(rand(), 0, 8);
        }
    }

    /**
     * 隨機產生唯一majorCode
     *
     * @return string
     */
    private function getUniqueMajorCode(): string
    {
        for ($i = 1; $i > 0; $i++) {

            $majorCode = config('app.code') . substr(rand(), 0, 6);

            if (!$this->model->query()->where('majorcode', $majorCode)->exists()) {
                return $majorCode;
            }
        }
    }
}
