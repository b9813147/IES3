<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `notification_member` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer mid
 * @property integer nid
 * @property integer MemberID
 * @property integer is_read
 * @property string  read_dt
 * @property integer status
 * @property integer flag
 *
 */
class NotificationMemberEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_member';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'mid';

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
}