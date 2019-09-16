<?php

namespace App\Repositories;

use App\Models\Fc_outline;
use Illuminate\Support\Collection;
use Yish\Generators\Foundation\Repository\Repository;
use App\Repositories\Base\BaseRepository;

class Fc_outlineRepository extends BaseRepository
{
    protected $model;

    public function __construct(Fc_outline $fc_outline)
    {
        $this->model = $fc_outline;
    }

    /**
     *  取得公開課綱ID
     *
     * @return Collection
     */
    public function getPublicOutlineId()
    {
        return $this->model->query()->select('id')
            ->where('publicflag', 'E')
            ->pluck('id');
    }

}
