<?php

namespace App\Http\Controllers\Api\V1\Courses;

use App\ExceptionCodes\CourseExceptionCode;
use App\Models\Major;
use App\Services\MajorService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\CourseStudentsResource;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Services\CourseStudentService;
use App\Services\CourseService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * 學生資料
 *
 * @package App\Http\Controllers\Api\V1\Courses
 */
class StudentController extends BaseApiV1Controller
{
    /** @var CourseService */
    protected $courseService;

    protected $majorService;

    /**
     * StudentController constructor.
     *
     * @param CourseService $courseService
     * @param MajorService $majorService
     */
    public function __construct(CourseService $courseService, MajorService $majorService)
    {
        $this->courseService = $courseService;
        $this->majorService  = $majorService;
    }

    /**
     * 查詢課程的學生資料清單
     *
     * @param CourseStudentService $service
     * @param integer $courseNO 課程代號
     *
     * @return CourseStudentsResource
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function index(CourseStudentService $service, $courseNO)
    {
        $user = $this->auth->user();

        // 檢查是否為老師的課程
        if (!$this->courseService->isMyCourseByTeacher($user->MemberID, $courseNO)) {
            throw new AccessDeniedHttpException();
        }

        return CourseStudentsResource::make((object)array('course_no' => $courseNO, 'students' => $service->getStudents($courseNO)));
    }

    /**
     * 學生清單編輯
     *
     * @param Request $request
     * @param $courseNO
     * @return JsonResponse
     */
    public function edit(Request $request, $courseNO)
    {
        $user     = $this->auth->user();
        $students = collect($request->students);

        try {
            Major::query()->findOrFail($courseNO);

            // 檢查是否為老師的課程
            if (!$this->courseService->isMyCourseByTeacher($user->MemberID, $courseNO)) {
                throw new AccessDeniedHttpException();
            }

            if (!$request->exists('students')) {

                return response()->json(['message' => 'students format error '], 404);
            }

            // 原始數據數量 !=  判斷唯一值得數量 兩者數量不同 等於有重複的 seat_no
            if ($students->count() != $students->unique('seat_no')->count()) {
                return response()->json(['message' => 'seat_no must is unique'], 404);
            }

            return $this->majorService->studentFormatInspectByUpdateMajor($courseNO, $students)
                ? response()->json(['message' => 'success'], '200')
                : response()->json(['message' => 'fail'], '404');

        } catch (\Exception $exception) {
            throw new ResourceException('課程不存在', null, null, [], CourseExceptionCode::COURSE_ID_NOT_FOUND);
        }

    }
}