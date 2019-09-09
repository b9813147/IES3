<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class NotificationRepository
 *
 * @package App\Repositories\Eloquent
 */
class NotificationRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\NotificationEntity";
    }

    public function boot()
    {

    }
}