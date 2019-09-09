<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class EventsRepository
 *
 * @package App\Repositories\Eloquent
 */
class EventsRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\EventsEntity";
    }

    public function boot()
    {

    }
}