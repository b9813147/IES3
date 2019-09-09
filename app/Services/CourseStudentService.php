<?php

namespace App\Services;

use App\Repositories\Eloquent\CourseStudentRepository;

/**
 * 課程的學生相關 Service
 *
 * @package App\Services
 */
class CourseStudentService
{
    /** @var CourseStudentRepository */
    protected $courseStudentRepository;

    /**
     * CourseStudentService constructor.
     *
     * @param CourseStudentRepository $courseStudentRepository
     */
    public function __construct(CourseStudentRepository $courseStudentRepository)
    {
        $this->courseStudentRepository = $courseStudentRepository;
    }

    /**
     * 查詢課程學生清單
     *
     * @param integer $courseNO
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getStudents($courseNO)
    {
        return $this->courseStudentRepository->findForCourse($courseNO);
    }

    /**
     * 學生是否在課程裡
     *
     * @param integer $courseNo
     * @param integer $studentMemberId
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function isInMajor($courseNo, $studentMemberId)
    {
        $students = $this->courseStudentRepository->findForCourse($courseNo, array($studentMemberId));
        if ($students->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * 查詢課程單一學生
     *
     * @param integer $courseNo
     * @param integer $studentMemberId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getStudent($courseNo, $studentMemberId)
    {
        $students = $this->courseStudentRepository->findForCourse($courseNo, array($studentMemberId));

        return $students->first();
    }
}