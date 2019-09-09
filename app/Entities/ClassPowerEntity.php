<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `classpower` entity
 *
 * @package App\Entities
 */
class ClassPowerEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classpower';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ser';

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
}