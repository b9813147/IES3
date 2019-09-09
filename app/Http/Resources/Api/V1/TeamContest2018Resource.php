<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class TeamContest2018Resource
 *
 * @package App\Http\Resources\Api\V1
 */
class TeamContest2018Resource extends Resource
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
            'event_name' => $this->event_name,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
        ];
    }
}