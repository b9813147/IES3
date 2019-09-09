<?php

namespace App\Services;

use App\Repositories\CourseRepository;
use App\Repositories\ClassPowerRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yish\Generators\Foundation\Service\Service;

/**
 * 查詢課程相關 Service
 *
 * @package App\Services
 */
class CourseService extends Service
{
    /** @var CourseRepository */
    protected $courseRepository;

    /** @var ClassPowerRepository */
    protected $classPowerRepository;

    /**
     * CourseService constructor.
     *
     * @param CourseRepository $courseRepository
     * @param ClassPowerRepository $classPowerRepository
     */
    public function __construct(CourseRepository $courseRepository, ClassPowerRepository $classPowerRepository)
    {
        $this->courseRepository     = $courseRepository;
        $this->classPowerRepository = $classPowerRepository;
    }

    /**
     * 查詢課程清單
     *
     * @param $memberID
     * @param $sno
     *
     * @return mixed
     *
     */
    public function getAllCourses($memberID, $sno)
    {
        return $this->getAllCoursesForTeacher($memberID, $sno);
    }

    /**
     * 查詢老師課程清單
     *
     * @param integer $memberID
     * @param integer|array $sno
     *
     * @return mixed
     *
     */
    public function getAllCoursesForTeacher($memberID, $sno)
    {
        // 取出任課的課程清單
        $masterCourses = $this->courseRepository->findForMasterTeacher($memberID, $sno);

        // 取出協同的課程清單
        $subjectCourses = $this->courseRepository->findForSubjectTeacher($memberID, $sno);

        // 設定參數 is_master：判斷是否為任課的課程
        $masterCourses->map(function ($value) {
            $value->is_master = true;
        });
        $subjectCourses->map(function ($value) {
            $value->is_master = false;
        });

        return $masterCourses->merge($subjectCourses);
    }

    /**
     * 查詢是否為老師的課程，包含任課和協同課程
     *
     * @param integer $memberID
     * @param integer $courseNO
     *
     * @return bool
     *
     */
    public function isMyCourseByTeacher($memberID, $courseNO)
    {
        if ($this->courseRepository->findWhere(['CourseNO' => $courseNO, 'MemberID' => $memberID])->isNotEmpty()) {
            return true;
        }

        if ($this->classPowerRepository->findWhere(['ClassID' => $courseNO, 'MemberID' => $memberID])->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * 新增課程
     *
     * @param $request
     * @param $user
     * @return Builder|Model
     */
    public function createCourseByTeacher($request, $user)
    {
        return $this->courseRepository->createCourseByTeacher($request, $user);
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
        return $this->courseRepository->editByCourse($request, $courseNO);
    }

    /**
     * 更新課程邀請碼
     *
     * @param $request
     * @param $courseNO
     * @param int $tmCourse
     * @param string $manageBy
     * @return bool
     */
    public function updateMajorCodeByCourse($request, $courseNO, $tmCourse = 1, $manageBy = 'T')
    {
        return $this->courseRepository->findWhere(['CourseNO' => $courseNO, 'tmcourse' => $tmCourse, 'manageby' => $manageBy])->isNotEmpty()
            ? $this->courseRepository->updateMajorCodeByCourse($request, $courseNO)
            : false;
    }

    /**
     * 取得課程資訊
     *
     * @param $courseNO
     * @return mixed
     */
    public function getByCourses($courseNO)
    {
        return $this->courseRepository->getCourses($courseNO);
    }
}