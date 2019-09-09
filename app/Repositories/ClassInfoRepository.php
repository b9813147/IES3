<?php

namespace App\Repositories;

use App\Models\Classinfo;
use App\Repositories\Base\BaseRepository;

class ClassInfoRepository extends BaseRepository
{
    protected $model;

    public function __construct(Classinfo $classInfo)
    {
        $this->model = $classInfo;
    }
}
