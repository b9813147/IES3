<?php

namespace App\Http\Controllers\Api\V1\Courses;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\Courses\StoreStudentAvatarRequest;
use App\Http\Resources\Api\V1\CourseStudentResource;
use App\Services\CourseService;
use App\Services\CourseStudentService;
use App\Services\UserAvatarService;
use Dingo\Api\Exception\ResourceException;
use App\ExceptionCodes\CourseExceptionCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 學生頭像
 *
 * @package App\Http\Controllers\Api\V1\Users
 */
class StudentAvatarController extends BaseApiV1Controller
{
    /** @var CourseService */
    protected $courseService;

    /** @var CourseStudentService */
    protected $courseStudentService;

    /** @var CourseStudentService */
    protected $userAvatarService;

    /**
     * StudentController constructor.
     *
     * @param CourseService $courseService
     * @param CourseStudentService $courseStudentService
     * @param UserAvatarService $userAvatarService
     */
    public function __construct(
        CourseService $courseService,
        CourseStudentService $courseStudentService,
        UserAvatarService $userAvatarService
    )
    {
        $this->courseService = $courseService;
        $this->courseStudentService = $courseStudentService;
        $this->userAvatarService = $userAvatarService;
    }

    /**
     * 上傳課程學生頭像
     *
     * @param StoreStudentAvatarRequest $request
     * @param integer $courseNo 課程代號
     * @param integer $memberId 學生 MemberID
     *
     * @return CourseStudentResource
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function store(StoreStudentAvatarRequest $request, $courseNo, $memberId)
    {
        $user = $this->auth->user();

        // 檢查是否為老師的課程
        if (!$this->courseService->isMyCourseByTeacher($user->MemberID, $courseNo)) {
            throw new AccessDeniedHttpException();
        }

        // 檢查學生是否在課程裡
        if (!$this->courseStudentService->isInMajor($courseNo, $memberId)) {
            throw new ResourceException(
                '課程裡沒有學生資料',
                null,
                null,
                [],
                CourseExceptionCode::COURSE_STUDENT_NOT_FOUND
            );
        }

        // 上傳頭像
        $this->userAvatarService->uploadedFile($memberId, $request->file('file'));

        // 取回傳的學生資料
        $student = $this->courseStudentService->getStudent($courseNo, $memberId);
        if (!$student) {
            throw new ResourceException(
                '課程裡沒有學生資料',
                null,
                null,
                [],
                CourseExceptionCode::COURSE_STUDENT_NOT_FOUND
            );
        }

        return new CourseStudentResource($student);
    }
}