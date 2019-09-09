<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Table `oauth2_member` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer MemberID
 * @property string  oauth2_account
 * @property string  sso_server
 * @property string  dt
 *
 * Relationships
 * @property mixed belongsToSystemAuthority
 */
class Oauth2MemberEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth2_member';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
     * 關聯 Table `systemauthority` entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsToSystemAuthority()
    {
        return $this->belongsTo('App\Entities\SystemAuthorityEntity', 'MemberID', 'MemberID');
    }
}