<?php

namespace App\Http\Controllers\Api\V1\Messages;

use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\Messages\GetMessageRequest;
use App\Http\Requests\Api\V1\Messages\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageCollection;
use App\Http\Resources\Api\V1\SendMessageResource;
use App\Services\MessagePaginationService;
use App\Services\CourseService;
use App\Services\SendMessageService;
use App\Exceptions\RepositoryException;
use App\Exceptions\CursorPaginationException;
use App\Exceptions\CourseStudentNotFound;
use App\ExceptionCodes\CourseExceptionCode;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * 電子紙條資料
 *
 * @package App\Http\Controllers\Api\V1\Messages
 */
class MessageController extends BaseApiV1Controller
{
    /**
     * MessageController constructor.
     */
    public function __construct()
    {

    }

    /**
     * 查詢電子紙條資料清單
     *
     * @param MessagePaginationService $service
     * @param GetMessageRequest $request
     *
     * @return MessageCollection
     *
     * @throws RepositoryException
     */
    public function index(MessagePaginationService $service, GetMessageRequest $request)
    {
        $limit = $request->input('limit');
        $prev = $request->input('prev_id');
        $next = $request->input('next_id');

        try {
            $user = $this->auth->user();

            //取得電子紙條清單
            $messages = $service->getAllMessages($user->MemberID, $prev, $next, $limit);

            $collection = new MessageCollection($messages);
            $collection::wrap('messages');

            return $collection;
        } catch (CursorPaginationException $e) {
            throw new BadRequestHttpException();
        }
    }

    /**
     * 發送電子紙條
     *
     * @param CourseService $courseService
     * @param SendMessageService $sendMessageService
     * @param StoreMessageRequest $request
     *
     * @return SendMessageResource
     *
     * @throws RepositoryException
     */
    public function store(CourseService $courseService, SendMessageService $sendMessageService, StoreMessageRequest $request)
    {
        $courseNo = $request->input('recipient.course.course_no');
        $students = $request->input('recipient.course.students.*.member_id');
        $title = $request->input('message.title');
        $content = $request->input('message.content');

        // 檢查是否為老師的課程
        $user = $this->auth->user();
        if (!$courseService->isMyCourseByTeacher($user->MemberID, $courseNo)) {
            throw new AccessDeniedHttpException();
        }

        try {
            // 發送電子紙條
            $message = $sendMessageService->sendByCourse($courseNo, $students, $user->MemberID, $title, $content);

            return new SendMessageResource($message);
        } catch (CourseStudentNotFound $e) {
            throw new ResourceException('課程裡沒有學生資料', null, null, [], CourseExceptionCode::COURSE_STUDENT_NOT_FOUND);
        }
    }
}