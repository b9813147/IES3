<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `bb_period_order_users` entity
 *
 * @package App\Entities
 */
class BbPeriodOrderUsersEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bb_period_order_users';

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
    public function belongsToBbPeriodOrders()
    {
        return $this->belongsTo('App\Entities\BbPeriodOrdersEntity', 'bb_period_orders_id', 'id');
    }
}