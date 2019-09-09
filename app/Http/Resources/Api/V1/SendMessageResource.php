<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class CourseStudentsResource
 *
 * @package App\Http\Resources\Api\V1
 */
class SendMessageResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'message_id' => $this->MsgID,
            'sender_member_id' => $this->SendMemberID,
            'sender_name' => $this->belongsToMember->RealName,
            'send_date' => $this->SendTime->toIso8601String(),
            'title' => $this->MsgTitle,
            'content' => $this->MsgContent,
            'attachments' => new MessageAttachmentCollection($this->attachments),
            'recipients' => $this->when($this->recipients, function () {
                return new SendMessageRecipientCollection($this->recipients);
            })
        ];
    }
}