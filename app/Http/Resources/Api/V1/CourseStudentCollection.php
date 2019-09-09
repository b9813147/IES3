<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class CourseStudentCollection
 *
 * @package App\Http\Resources\Api\V1
 */
class CourseStudentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($student) {
            return [
                'member_id'     => $student->MemberID,
                'student_id'    => $student->CivilID,
                'name'          => $student->RealName,
                'login_id'      => $student->LoginID,
                'seat_no'       => $student->SeatNO,
                'avatar_url'    => avatar_url($student->MemberID, $student->HeadImg),
                'avatar_name'   => $student->HeadImg,
                'team_model_id' => $student->oauth2_account,
                "GroupNO"       => $student->GroupNO,
                "GroupName"     => $student->GroupName,
                "RemoteNO"      => $student->RemoteNO,
                "GrpMemberNO"   => $student->GrpMemberNO,
            ];
        })->toArray();
    }
}