<?php

namespace App\Services;

use App\Repositories\Eloquent\PersonalMsgRepository;
use App\Repositories\Eloquent\PersonalMsgAttachmentRepository;

/**
 * 查詢電子紙條相關 Service
 *
 * @package App\Services
 */
class MessagePaginationService
{
    /** @var PersonalMsgRepository */
    protected $personalMsgRepository;

    protected $personalMsgAttachmentRepository;

    /**
     * MessageService constructor.
     *
     * @param PersonalMsgRepository $personalMsgRepository
     * @param PersonalMsgAttachmentRepository $personalMsgAttachmentRepository
     */
    public function __construct(PersonalMsgRepository $personalMsgRepository, PersonalMsgAttachmentRepository $personalMsgAttachmentRepository)
    {
        $this->personalMsgRepository = $personalMsgRepository;
        $this->personalMsgAttachmentRepository = $personalMsgAttachmentRepository;
    }

    /**
     * 取得個人紙條清單
     *
     * @param $memberID
     * @param string|null $prev
     * @param string|null $next
     * @param integer|null $limit
     * @param string $direction
     * @return mixed
     * @throws \App\Exceptions\CursorPaginationException
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAllMessages($memberID, $prev = null, $next = null, $limit = null, $direction = 'desc')
    {
        $messages = $this->personalMsgRepository->getAllMessagesByTimeCursor($memberID, $prev, $next, $limit, $direction);

        // 電子紙條附件
        $messages->getCollection()->transform(function ($message) {
            $message->attachments = $this->personalMsgAttachmentRepository->findWhere(['MsgID' => $message->MsgID]);
            return $message;
        });

        return $messages;
    }
}