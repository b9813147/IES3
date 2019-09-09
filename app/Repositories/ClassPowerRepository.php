<?php

namespace App\Repositories;

use App\Models\Classpower;
use App\Repositories\Base\BaseRepository;

class ClassPowerRepository extends BaseRepository
{
    protected $model;

    public function __construct(Classpower $classPower)
    {
        $this->model = $classPower;
    }
}
