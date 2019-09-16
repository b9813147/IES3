<?php

namespace App\Repositories;

use App\Models\SemesterInfo;
use Illuminate\Database\Eloquent\Model;
use Yish\Generators\Foundation\Repository\Repository;
use App\Repositories\Base\BaseRepository;
class SemesterInfoRepository extends BaseRepository
{
    protected $model;

    public function __construct(SemesterInfo $semesterInfo)
    {
        $this->model = $semesterInfo;
    }
    /**
     * @param $attributes
     * @param $values
     * @return Model
     */
    public function firstOrCreate($attributes, $values)
    {
        return $this->model->query()->firstOrCreate($attributes, $values);
    }
}


