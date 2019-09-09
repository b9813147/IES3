<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class Oauth2MemberLogsRepository
 *
 * @package App\Repositories\Eloquent
 */
class Oauth2MemberLogsRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\Oauth2MemberLogsEntity";
    }

    public function boot()
    {

    }
}