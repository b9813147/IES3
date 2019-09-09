<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class SemesterInfoRepository
 *
 * @package App\Repositories\Eloquent
 */
class SemesterInfoRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\SemesterInfoEntity";
    }

    public function boot()
    {

    }

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     * @param array $values
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function firstOrCreate(array $attributes = [], array $values = [])
    {
        $this->applyCriteria();

        if (! is_null($instance = $this->model->where($attributes)->first())) {
            $this->resetModel();
            return $instance;
        }

        $sno = $this->model->max('SNO');
        $sno = (empty($sno)) ? 1 : $sno + 1;

        return tap($this->model->newModelInstance($attributes + ['SNO' => $sno]), function ($instance) {
            $instance->save();
            $this->resetModel();
        });
    }
}