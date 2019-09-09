<?php

namespace App\Repositories\Contracts;

/**
 * Interface CriteriaInterface
 *
 * @package App\Repositories\Contracts
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param                     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository);
}