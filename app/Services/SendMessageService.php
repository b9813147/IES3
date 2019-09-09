<?php

namespace App\Services;

use App\Repositories\Eloquent\CourseStudentRepository;
use App\Repositories\Eloquent\PersonalMsgRepository;
use App\Repositories\Eloquent\PersonalMsgAttachmentRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Constants\Models\NotificationConstant;
use App\Constants\Models\PersonalMsgConstant;
use App\Exceptions\CourseStudentNotFound;

/**
 * 發送電子紙條相關 Service
 *
 * @package App\Services
 */
class SendMessageService
{
    /** @var CourseStudentRepository */
    protected $courseStudentRepository;

    /** @var PersonalMsgRepository */
    protected $personalMsgRepository;

    /** @var PersonalMsgAttachmentRepository */
    protected $personalMsgAttachmentRepository;

    /** @var NotificationRepository */
    protected $notificationRepository;

    /**
     * MessageService constructor.
     *
     * @param CourseStudentRepository $courseStudentRepository
     * @param PersonalMsgRepository $personalMsgRepository
     * @param PersonalMsgAttachmentRepository $personalMsgAttachmentRepository
     * @param NotificationRepository $notificationRepository
     */
    public function __construct(
        CourseStudentRepository $courseStudentRepository,
        PersonalMsgRepository $personalMsgRepository,
        PersonalMsgAttachmentRepository $personalMsgAttachmentRepository,
        NotificationRepository $notificationRepository
    )
    {
        $this->courseStudentRepository = $courseStudentRepository;
        $this->personalMsgRepository = $personalMsgRepository;
        $this->personalMsgAttachmentRepository = $personalMsgAttachmentRepository;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * 依課程發送電子紙條
     *
     * @param integer $toCourseNo 收件的課程 CourseNO
     * @param array|null $students 指定收件的學生 MemberID
     * @param integer $fromMemberId 寄件者 MemberID
     * @param string $title 電子紙條標題
     * @param $content 電子紙條內容
     *
     * @return mixed
     *
     * @throws CourseStudentNotFound
     * @throws \App\Exceptions\RepositoryException
     */
    public function sendByCourse($toCourseNo, $students = null, $fromMemberId, $title, $content)
    {
        // 取課程所有學生
        $students = $this->courseStudentRepository->findForCourse($toCourseNo, $students);
        if ($students->isEmpty()) {
            throw new CourseStudentNotFound();
        }

        // 取出所有學生的 MemberID
        $memberIds = [];
        $students->each(function ($item, $key) use (&$memberIds) {
            $memberIds[] = $item->MemberID;
        });

        // 發送電子紙條
        $messages = $this->send($memberIds, $fromMemberId, $title, $content);

        // 增加回傳電子紙條附件
        $messages->first()->attachments = $this->personalMsgAttachmentRepository->findWhere(['MsgID' => $messages->first()->MsgID]);

        // 增加回傳收件人
        $messages->first()->recipients = $students;

        return $messages->first();
    }

    /**
     * 發送電子紙條
     *
     * @param integer|array $toMemberIds 收件者 MemberID
     * @param integer $fromMemberId 寄件者 MemberID
     * @param string $title 電子紙條標題
     * @param string $content 電子紙條內容
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function send($toMemberIds, $fromMemberId, $title, $content)
    {
        $toMemberIds = is_array($toMemberIds) ? $toMemberIds : [$toMemberIds];
        $sendTime = now()->toDateTimeString();

        $sendData = [];
        foreach ($toMemberIds as $memberId) {
            $sendData[] = [
                'MemberID' => $memberId,
                'SendMemberID' => $fromMemberId,
                'SendTime' => $sendTime,
                'MsgTitle' => $title,
                'MsgContent' => $content,
                'GetStatus' => PersonalMsgConstant::GET_STATUS_UNREAD,
                'SendStatus' => PersonalMsgConstant::SEND_STATUS_NOT_DELETED,
            ];
        }

        $messages = $this->personalMsgRepository->createMessageToMany($sendData);

        // 發送 AClass ONE 通知，之後需改寫成使用 Redis
        $notification = $this->notificationRepository->create([
            'MemberID' => $fromMemberId,
            'ntype' => NotificationConstant::N_TYPE__E_PAPER_ADD,
            'MsgID' => $messages[0]->MsgID,
            'send_dt' => $sendTime,
            'end_dt' => $sendTime,
            'AsignFlag' => NotificationConstant::ASIGN_FLAG_TRUE,
        ]);

        $messages->each(function ($item, $key) use ($notification) {
            $notification->hasManyNotificationMember()->create([
                'MemberID' => $item->MemberID,
            ]);
        });

        return $messages;
    }
}