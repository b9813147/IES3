<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class CourseRepository
 *
 * @package App\Repositories\Eloquent
 */
class CourseRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\CourseEntity";
    }

    public function boot()
    {

    }

    /**
     * 查詢老師的任課課程
     *
     * @param integer $memberID
     * @param integer|array $sno
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForMasterTeacher($memberID, $sno)
    {
        $model = $this->model->select('course.*', 'semesterinfo.*')
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

        $this->resetModel();

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
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForSubjectTeacher($memberID, $sno)
    {
        $model = $this->model->select('course.*', 'semesterinfo.*')
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

        $this->resetModel();

        return $model;
    }
}