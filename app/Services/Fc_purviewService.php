<?php

namespace App\Services;

use App\Repositories\Fc_purviewRepository;
use Yish\Generators\Foundation\Service\Service;

class Fc_purviewService
{
    protected $repository;

    public function __construct(Fc_purviewRepository $fc_purviewRepository)
    {
        $this->repository = $fc_purviewRepository;
    }

    /**
     *  新增公開分享課綱
     *
     * @param $memberID
     * @param $fc_outline_id
     * @return bool
     */
    public function AddPurview($memberID, $fc_outline_id)
    {
        return $this->repository->AddPurview($memberID, $fc_outline_id);
    }

    /**
     * 取得分享名單Fc_outline_id
     *
     * @param $memberID
     * @return mixed
     */
    public function getFcPurviewID($memberID)
    {
        return $this->getFcPurviewID($memberID);
    }
}
