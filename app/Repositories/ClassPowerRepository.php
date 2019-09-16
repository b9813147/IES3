<?php

namespace App\Repositories;

use App\Models\Classpower;
use App\Repositories\Base\BaseRepository;
use Illuminate\Support\Collection;

class ClassPowerRepository extends BaseRepository
{
    protected $model;

    public function __construct(Classpower $classPower)
    {
        $this->model = $classPower;
    }

    /**
     * 取協同老師 課程ID
     *
     * @param $memberID
     * @return Collection
     */
    public function getByTeacherClassID($memberID)
    {
        return $this->model->query()->where('MemberID', $memberID)->pluck('ClassID');
    }

    /**
     * 增加協同老師
     *
     * @param $AcademicYear
     * @param $SOrder
     * @param $courseNO
     * @param $memberID
     * @return bool
     */
    public function add($AcademicYear, $SOrder, $courseNO, $memberID)
    {
        return $this->model->query()->insert([
            'AcademicYear' => $AcademicYear,
            'Sorder'       => $SOrder,
            'classtype'    => '2',
            'ClassID'      => $courseNO,
            'powertype'    => 'Z',
            'MemberID'     => $memberID,
        ]);
    }

    /**
     * 刪除協同老師 課程
     *
     * @param $CourseNO
     *
     */
    public function deleteByClassNO($CourseNO)
    {
        $this->model->query()->whereIn('ClassID', $CourseNO)->delete();
    }
}
