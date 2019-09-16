<?php

namespace App\Services;

use App\Repositories\Fc_outlineRepository;
use Illuminate\Support\Collection;
use Yish\Generators\Foundation\Service\Service;

class Fc_outlineService extends Service
{
    protected $repository;

    public function __construct(Fc_outlineRepository $fc_outlineRepository)
    {
        $this->repository = $fc_outlineRepository;
    }

    /**
     * 取得公開課綱
     *
     * @return Fc_outlineRepository[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getPublicOutline()
    {
        return $this->repository->getBy('publicflag', 'E');
    }

    /**
     * 取得公開課綱ＩＤ
     * @return Collection
     */
    public function getPublicOutlineId()
    {
        return $this->repository->getPublicOutlineId();
    }


}
