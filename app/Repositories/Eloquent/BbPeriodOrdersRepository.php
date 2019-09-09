<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class BbPeriodOrdersRepository
 *
 * @package App\Repositories\Eloquent
 */
class BbPeriodOrdersRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\BbPeriodOrdersEntity";
    }

    public function boot()
    {

    }

    /**
     * 取得學校的銷售授權訂單，並且訂單是在有效時間內及審核狀態為通過
     *
     * @param integer $schoolId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getOrdersBySchoolId($schoolId)
    {
        $periodWhere = [
            'SchoolID' => $schoolId,
            ['start_date', '<=', today()->toDateString()],
            ['end_date', '>=', today()->toDateString()],
            'period_type' => 0
        ];

        $orders = $this->whereHas('belongsToBbPeriods', function ($query) use ($periodWhere) {
            $query->where($periodWhere);
        })->findWhere(['order_audit' => 1]);

        return  $orders;
    }

    /**
     * 取得所有學校的銷售授權訂單，並且訂單是在有效時間內及審核狀態為通過
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAllSchoolOrders()
    {
        $periodWhere = [
            ['start_date', '<=', today()->toDateString()],
            ['end_date', '>=', today()->toDateString()],
            'period_type' => 0
        ];

        $orders = $this->with('belongsToBbPeriods')->whereHas('belongsToBbPeriods', function ($query) use ($periodWhere) {
            $query->where($periodWhere);
        })->findWhere(['order_audit' => 1]);

        return  $orders;
    }
}