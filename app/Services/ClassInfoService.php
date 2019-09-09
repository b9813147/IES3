<?php

namespace App\Services;

use App\Repositories\ClassInfoRepository;
use Illuminate\Database\Eloquent\Collection;
use Yish\Generators\Foundation\Service\Service;

class ClassInfoService extends Service
{
    protected $repository;

    public function __construct(ClassInfoRepository $classInfoRepository)
    {
        $this->repository = $classInfoRepository;
    }

    /**
     * 取得全部年級資料
     *
     * @return ClassInfoRepository[]|Collection
     */
    public function getGrades()
    {
       return $this->repository->all();
    }
}
