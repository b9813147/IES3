<?php


namespace App\Repositories\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Yish\Generators\Foundation\Repository\Repository;
abstract class BaseRepository extends  Repository
{
    /**
     * @var \Eloquent|\DB
     */
    protected $model;

    /**
     * @param array $where
     * @param array $columns
     * @return Builder[]|Collection
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        return $this->model->where($where)->get($columns);
    }
}