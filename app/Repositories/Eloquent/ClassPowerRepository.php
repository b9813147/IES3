<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class ClassPowerRepository
 *
 * @package App\Repositories\Eloquent
 */
class ClassPowerRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\ClassPowerEntity";
    }

    public function boot()
    {

    }
}