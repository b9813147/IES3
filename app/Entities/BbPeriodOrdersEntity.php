<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `bb_period_orders` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer id
 */
class BbPeriodOrdersEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bb_period_orders';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * 關聯 Table `bb_period_order_users` entity
     */
    public function hasManyBbPeriodOrderUsers()
    {
        return $this->hasMany('App\Entities\BbPeriodOrderUsersEntity', 'bb_period_orders_id', 'id');
    }

    /**
     * 關聯 Table `bb_periods` entity
     */
    public function belongsToBbPeriods()
    {
        return $this->belongsTo('App\Entities\BbPeriodsEntity', 'bb_periods_id', 'id');
    }

}