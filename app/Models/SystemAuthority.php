<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SystemAuthority extends Model
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

    protected $fillable = [
        'MemberID',
        'IDLevel',
        'SystemManager',
        'ItembankManager',
        'CareerTitle',
        'Experiment',
        'ActiveManager',
        'ActiveManagerSec',
        'MaterialSize',
        'EzcmsSize',
        'SDate',
        'EDate',
        'Promoter',
        'analysis',
        'serial',
    ];


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

    public function Member()
    {
        return $this->belongsTo(Member::class, 'MemberID');
    }
}