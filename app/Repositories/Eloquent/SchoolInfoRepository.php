<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class SchoolInfoRepository
 *
 * @package App\Repositories\Eloquent
 */
class SchoolInfoRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\SchoolInfoEntity";
    }

    public function boot()
    {

    }

    /**
     * 依學校代碼或學校簡碼取得學校資料
     *
     * @param string|null $schoolCode 學校代碼
     * @param string|null $abbr 學校簡碼
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getSchoolByCodeOrAbbr($schoolCode, $abbr)
    {
        $model = null;
        if (!empty($schoolCode) && !empty($abbr)) {
            $model = $this->model
                ->where('Status', 1)
                ->where(function ($query) use ($schoolCode, $abbr) {
                    $query->where('SchoolCode', $schoolCode)->orWhere('Abbr', $abbr);
                })
                ->first();
        } elseif (!empty($schoolCode)) {
            $model = $this->model
                ->where('Status', 1)
                ->where('SchoolCode', $schoolCode)
                ->first();
        } elseif (!empty($abbr)) {
            $model = $this->model
                ->where('Status', 1)
                ->where('Abbr', $abbr)
                ->first();
        }

        if (!is_null($model)) {
            $this->resetModel();
        }

        return $model;
    }

    /**
     * 依學校代碼和學校簡碼建立學校資料
     *
     * @param string|null $schoolCode 學校代碼
     * @param string|null $abbr 學校簡碼
     * @param array $values
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createSchoolByCodeOrAbbr($schoolCode, $abbr, array $values = [])
    {
        $schoolId = $this->model->max('SchoolID');
        $schoolId = (empty($schoolId)) ? 1 : $schoolId + 1;

        $values['SchoolID'] = $schoolId;
        $values['SchoolCode'] = (empty($schoolCode)) ? $this->createSchoolCode($schoolId) : $schoolCode;
        $values['Abbr'] = (empty($abbr)) ? $this->createSchoolAbbr($schoolId) : $abbr;

        $school = $this->getSchoolByCodeOrAbbr($values['SchoolCode'], $values['Abbr']);
        if (!is_null($school)) {
            return null;
        }

        return tap($this->model->newModelInstance($values), function ($instance) {
            $instance->save();
            $this->resetModel();
        });
    }

    /**
     * 組合學校代碼
     *
     * @param integer $i
     *
     * @return string
     */
    private function createSchoolCode($i)
    {
        return config('app.ies_server_name') . str_pad($i, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 組合學校簡碼
     *
     * @param integer $i
     *
     * @return string
     */
    private function createSchoolAbbr($i)
    {
        return config('app.ies_server_name') . str_pad($i, 4, '0', STR_PAD_LEFT);
    }
}