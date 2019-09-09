<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

class PersonalMsgAttachmentRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\PersonalMsgAttachmentEntity";
    }

    public function boot()
    {

    }
}