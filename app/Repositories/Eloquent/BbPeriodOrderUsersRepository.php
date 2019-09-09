<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class BbPeriodOrderUsersRepository
 *
 * @package App\Repositories\Eloquent
 */
class BbPeriodOrderUsersRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\BbPeriodOrderUsersEntity";
    }

    public function boot()
    {

    }

    /**
     * 取得使用者的試用授權訂單，並且訂單是在有效時間內及審核狀態為通過
     *
     * @param array $teamModelIds
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getTrialOrdersByTeamModelIds($teamModelIds)
    {
        $model = $this->model->selectRaw('bb_period_order_users.team_model_id, SUM(bb_period_orders.storage) AS storage, SUM(bb_period_orders.socratic_report) AS socratic_report, SUM(bb_period_orders.socratic_video) AS socratic_video, SUM(bb_period_orders.socratic_hi_encoder) AS socratic_hi_encoder, SUM(bb_period_orders.socratic_dashboard) AS socratic_dashboard')
            ->join('bb_period_orders', 'bb_period_orders.id', '=', 'bb_period_order_users.bb_period_orders_id')
            ->join('bb_periods', 'bb_periods.id', '=', 'bb_period_orders.bb_periods_id')
            ->whereIn('bb_period_order_users.team_model_id', $teamModelIds)
            ->where('bb_period_orders.order_audit', 1)
            ->where('bb_periods.period_type', 1)
            ->where('bb_periods.start_date', '<=', today()->toDateString())
            ->where('bb_periods.end_date', '>=', today()->toDateString())
            ->groupBy('bb_period_order_users.team_model_id')
            ->get();

        $this->resetModel();

        return $model;
    }

    /**
     * 取得所有試用授權訂單，並且訂單是在有效時間內及審核狀態為通過
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAllTrialOrders()
    {
        $model = $this->model->selectRaw('bb_period_order_users.team_model_id, SUM(bb_period_orders.storage) AS storage, SUM(bb_period_orders.socratic_report) AS socratic_report, SUM(bb_period_orders.socratic_video) AS socratic_video, SUM(bb_period_orders.socratic_hi_encoder) AS socratic_hi_encoder, SUM(bb_period_orders.socratic_dashboard) AS socratic_dashboard')
            ->join('bb_period_orders', 'bb_period_orders.id', '=', 'bb_period_order_users.bb_period_orders_id')
            ->join('bb_periods', 'bb_periods.id', '=', 'bb_period_orders.bb_periods_id')
            ->where('bb_period_orders.order_audit', 1)
            ->where('bb_periods.period_type', 1)
            ->where('bb_periods.start_date', '<=', today()->toDateString())
            ->where('bb_periods.end_date', '>=', today()->toDateString())
            ->groupBy('bb_period_order_users.team_model_id')
            ->get();

        $this->resetModel();

        return $model;
    }
}