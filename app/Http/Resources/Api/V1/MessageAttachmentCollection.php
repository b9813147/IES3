<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class MessageAttachmentCollection
 *
 * @package App\Http\Resources\Api\V1
 */
class MessageAttachmentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($attachment) {
            return [
                'file_name' => $attachment->base_name ,
                'file_url' => app('Dingo\Api\Routing\UrlGenerator')->version('v1')->route(
                    "messages.attachments.index", ['msgId' => $attachment->MsgID, 'hashId' => $attachment->hash_id])
            ];
        })->toArray();
    }
}