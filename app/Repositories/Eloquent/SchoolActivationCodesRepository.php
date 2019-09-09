<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class SchoolInfoRepository
 *
 * @package App\Repositories\Eloquent
 */
class SchoolActivationCodesRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\SchoolActivationCodesEntity";
    }

    public function boot()
    {

    }

    /**
     * 查詢課程學生清單
     *
     * @param string $activationCode
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForSchool($activationCode)
    {
        $model = $this->model->select('school_activation_codes.*', 'schoolinfo.*')
            ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'school_activation_codes.SchoolID')
            ->where('school_activation_codes.activation_code', $activationCode)
            ->where('school_activation_codes.status', 1)
            ->where('schoolinfo.Status', 1)
            ->first();

        $this->resetModel();

        return $model;
    }
}