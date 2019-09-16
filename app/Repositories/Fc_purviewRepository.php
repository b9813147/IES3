<?php

namespace App\Repositories;

use App\Models\Fc_purview;
use Illuminate\Support\Collection as CollectionAlias;
use Yish\Generators\Foundation\Repository\Repository;
use App\Repositories\Base\BaseRepository;


class Fc_purviewRepository extends BaseRepository
{
    protected $model;

    public function __construct(Fc_purview $fc_purview)
    {
        $this->model = $fc_purview;
    }

    /**
     * 新增公開分享課綱
     *
     * @param $memberID
     * @param integer $fc_outline_id
     * @return bool
     */
    public function AddPurview($memberID, $fc_outline_id)
    {
        return $this->model->query()->insert([
            'MemberID'      => $memberID,
            'fc_outline_id' => $fc_outline_id,
            'purview'       => 'R'
        ]);
    }

    /**
     * 取得分享名單Fc_outline_id
     *
     * @param integer $memberID
     * @return CollectionAlias
     */
    public function getFcPurviewId($memberID)
    {
        return $this->model->query()->select('fc_outline_id')
            ->where('MemberID', $memberID)
            ->pluck('fc_outline_id');
    }
}
