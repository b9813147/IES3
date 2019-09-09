<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class EventUsersRepository
 *
 * @package App\Repositories\Eloquent
 */
class EventUsersRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\EventUsersEntity";
    }

    public function boot()
    {

    }
}