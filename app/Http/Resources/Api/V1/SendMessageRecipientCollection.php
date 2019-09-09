<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class MessageCollection
 *
 * @package App\Http\Resources\Api\V1
 */
class SendMessageRecipientCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($recipient) {
            return [
                'member_id' => $recipient->MemberID,
                'name' => $recipient->RealName,
                'login_id' => $recipient->LoginID,
            ];
        })->toArray();
    }
}