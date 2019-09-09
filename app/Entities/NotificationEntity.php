<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `notification` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer nid
 * @property integer ntype
 * @property integer MemberID
 * @property integer CourseNO
 * @property string  CourseName
 * @property string  content
 * @property string  created_dt
 * @property string  send_dt
 * @property string  end_dt
 * @property integer status
 * @property integer SNO
 * @property integer ExNO
 * @property integer HomeworkNO
 * @property integer fc_event_id
 * @property integer FiledID
 * @property integer rid
 * @property integer MsgID
 * @property integer AnnounceID
 * @property string  AsignFlag
 * @property string  ResultJson
 *
 */
class NotificationEntity extends Model
{
    const CREATED_AT = 'created_dt';

    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'nid';

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
    public $timestamps = true;

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
     * 關聯 Table `notification_member` entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasManyNotificationMember()
    {
        return $this->hasMany('App\Entities\NotificationMemberEntity', 'nid', 'nid');
    }
}