<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class CourseStudentResource
 *
 * @package App\Http\Resources\Api\V1
 */
class CourseStudentResource extends Resource
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
            'member_id' => $this->MemberID,
            'student_id' => $this->CivilID,
            'name' => $this->RealName,
            'login_id' => $this->LoginID,
            'seat_no' => $this->SeatNO,
            'avatar_url' => avatar_url($this->MemberID, $this->HeadImg),
            'avatar_name' => $this->HeadImg,
            'team_model_id' => $this->oauth2_account,
        ];
    }
}