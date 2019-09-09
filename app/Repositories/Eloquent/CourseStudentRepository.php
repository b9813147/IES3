<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class CourseStudentRepository
 *
 * @package App\Repositories\Eloquent
 */
class CourseStudentRepository extends BaseRepository
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
     * 查詢課程學生清單
     *
     * @param integer $courseNO
     * @param array|null $students 學生 MemberID
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForCourse($courseNO, $students = null)
    {
        $model = $this->model->select('major.*', 'member.*', 'oauth2_member.oauth2_account')
            ->join('major', 'course.CourseNO', '=', 'major.CourseNO')
            ->join('member', 'major.MemberID', '=', 'member.MemberID')
            ->leftJoin('oauth2_member', function ($join) {
                $join->on('member.MemberID', '=', 'oauth2_member.MemberID')
                    ->where('oauth2_member.sso_server', '=', 'HABOOK');
            })
            ->where('course.CourseNO', $courseNO)
            ->when(!empty($students), function ($query) use ($students) {
                return $query->whereIn('major.MemberID', $students);
            })
            ->orderBy('major.SeatNO', 'asc')
            ->get();

        $this->resetModel();

        return $model;
    }
}