<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `bb_periods` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer id
 * @property integer period_type
 */
class BbPeriodsEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bb_periods';

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
     * 關聯 Table `bb_period_orders` entity
     */
    public function hasManyBbPeriodOrders()
    {
        return $this->hasMany('App\Entities\BbPeriodOrdersEntity', 'bb_periods_id', 'id');
    }
}