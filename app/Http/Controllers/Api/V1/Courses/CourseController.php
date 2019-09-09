<?php

namespace App\Http\Controllers\Api\V1\Courses;

use App\ExceptionCodes\CourseExceptionCode;
use App\Exceptions\RepositoryException;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\Courses\CourseCreateRequest;
use App\Http\Requests\Api\V1\Courses\CourseMajorCodeRequest;
use App\Http\Requests\Api\V1\Courses\EditRequest;
use App\Http\Requests\Api\V1\Courses\GetCourseRequest;
use App\Http\Resources\Api\V1\ClassInfoCollection;
use App\Http\Resources\Api\V1\CourseCollection;
use App\Http\Resources\Api\V1\CourseResource;
use App\Services\ClassInfoService;
use App\Services\CourseService;
use App\Services\SemesterService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 課程資料
 *
 * @package App\Http\Controllers\Api\V1\Courses
 */
class CourseController extends BaseApiV1Controller
{
    protected $courseService;

    protected $classInfoService;

    /**
     * CourseController constructor.
     * @param CourseService $courseService
     * @param ClassInfoService $classInfoService
     */
    public function __construct(CourseService $courseService, ClassInfoService $classInfoService)
    {
        $this->courseService    = $courseService;
        $this->classInfoService = $classInfoService;
    }

    /**
     * 查詢課程資料清單
     *
     * 當沒有傳 sno 則預設指定本學期的課程
     *
     * @param CourseService $service
     * @param SemesterService $semesterService
     * @param GetCourseRequest $request
     *
     * @return CourseCollection
     *
     * @throws RepositoryException
     */
    public function index(CourseService $service, SemesterService $semesterService, GetCourseRequest $request)
    {
        $user = $this->auth->user();

        // 有 sno
        if ($request->filled('sno')) {
            $sno = $request->input('sno');
            $sno = explode(',', $sno);
        } else {
            // 沒有 sno，預設指定本學期
            $semester = $semesterService->getCurrentSemester();
            $sno      = $semester->SNO;
        }

        $courses = $service->getAllCourses($user->MemberID, $sno);
        CourseCollection::wrap('courses');
        return new CourseCollection($courses);
    }

    /**
     * 課程邀請碼
     * 只有TeamModel 可以使用課程邀請碼 tmCourse = 1
     *
     * @param CourseMajorCodeRequest $request
     * @param $courseNO integer
     * @return CourseResource
     */
    public function majorCode(CourseMajorCodeRequest $request, $courseNO)
    {
        try {
            return $this->courseService->updateMajorCodeByCourse($request, $courseNO)
                ? new CourseResource($this->courseService->getByCourses($courseNO))
                : response()->json(['message' => 'Not TeamModel course'], '404');

        } catch (ModelNotFoundException $exception) {
            return response()->json($exception->getMessage());
        }
    }

    /**
     * 新增課程
     * $ tmCourse  1 代表TeamModel Course 0 代表 一般課程
     * @param CourseCreateRequest $request
     * @return CourseResource
     */
    public function createCourse(CourseCreateRequest $request)
    {
        $user = $this->auth->user();

        $course = $this->courseService->createCourseByTeacher($request, $user);

        return new CourseResource($course);

    }

    /**
     * 取得年級資訊
     *
     */
    public function getGrades()
    {
        $classInfo = $this->classInfoService->getGrades();
        ClassInfoCollection::wrap('classInfo');

        return new ClassInfoCollection($classInfo);
    }

    /**
     * 課程編輯
     *
     * @param EditRequest $request
     * @param $courseNO
     * @return bool
     */
    public function edit(EditRequest $request, $courseNO)
    {
        try {

            return $this->courseService->editByCourse($request->all(), $courseNO)
                ? response()->json(['message' => 'success'], '200')
                : response()->json(['message' => 'fail'], '404');

        } catch (\Exception $exception) {
            throw new ResourceException('課程不存在', null, null, [], CourseExceptionCode::COURSE_ID_NOT_FOUND);
        }

    }
}