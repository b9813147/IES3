<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Table `systemauthority` entity
 *
 * @package App\Entities
 *
 * Column names
 * @property integer MemberID
 * @property string  IDLevel
 * @property integer SystemManager
 * @property integer ItembankManager
 * @property string  CareerTitle
 * @property integer Experiment
 * @property integer ActiveManager
 * @property integer ActiveManagerSec
 * @property integer MaterialSize
 * @property integer EzcmsSize
 * @property string  SDate
 * @property string  EDate
 * @property integer Promoter
 * @property integer analysis
 * @property string  serial
 */
class SystemAuthorityEntity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'systemauthority';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'MemberID';

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
     * 帳號是否在有效期內，目前不判斷啟用日
     *
     * @return boolean
     */
    public function getLicenseIsValidAttribute()
    {
        $nowDate = today();
        $endDate = Carbon::createFromFormat('Y-m-d', $this->EDate);

        if ($endDate < $nowDate) {
            return false;
        }

        return true;
    }
}