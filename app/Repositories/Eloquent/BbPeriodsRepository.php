<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class BbPeriodsRepository
 *
 * @package App\Repositories\Eloquent
 */
class BbPeriodsRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\BbPeriodsEntity";
    }

    public function boot()
    {

    }
}