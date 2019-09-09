<?php

namespace App\Repositories;

use App\Models\Major;
use  App\Repositories\Base\BaseRepository;

class MajorRepository extends BaseRepository
{
    protected $model;

    public function __construct(Major $major)
    {
        $this->model = $major;
    }

    /**
     * 老師建的課程，或協統老師都可更新
     *
     * @param $courseNO
     * @param $student
     * @return int
     */
    public function updateByT($courseNO, $student)
    {
        return $this->model->query()->where([
            'CourseNO' => $courseNO,
            'MemberID' => $student->member_id,
        ])
            ->update([
                'GroupNO'     => $student->GroupNO,
                'SeatNo'      => $student->seat_no,
                'GroupName'   => $student->GroupName,
                'RemoteNO'    => $student->RemoteNO,
                'GrpMemberNO' => $student->GrpMemberNO,
            ]);

    }

    /**
     * 學校建的課程
     *
     * @param $courseNO
     * @param $student
     * @return int
     */
    public function updateByS($courseNO, $student)
    {
        return $this->model->query()->where([
            'CourseNO' => $courseNO,
            'MemberID' => $student->member_id,
        ])
            ->update([
                'GroupNO'     => $student->GroupNO,
                'GroupName'   => $student->GroupName,
                'RemoteNO'    => $student->RemoteNO,
                'GrpMemberNO' => $student->GrpMemberNO,
            ]);
    }


}
