<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member';

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
    protected $hidden = [
        'Password', 'SsoUID',
    ];

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
        'LogID',
        'SchoolID',
        'LoginID',
        'Password',
        'NickName',
        'Email',
        'Homepage',
        'CivilID',
        'RealName',
        'Gender',
        'Birthday',
        'department',
        'Telephone',
        'cellphone',
        'familycellphone',
        'Address',
        'MRealName',
        'RelationShip',
        'MEmail',
        'HeadImg',
        'RegisterTime',
        'LoginTimes',
        'LastLoginTime',
        'LastLoginHost',
        'Status',
        'DEPARTMENTCODE',
        'source',
        'SsoUID',
    ];
    /**
     * 驗證使用者帳號
     *
     * 此方法為改變 passport 預設的驗證使用者帳號邏輯
     * 請參考 \vendor\laravel\passport\src\Bridge\UserRepository.php
     *
     * @param $username
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findForPassport($username)
    {
        return $this->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->join('schoolinfo', 'member.SchoolID', '=', 'schoolinfo.SchoolID')
            ->where('member.Status', 1)
            ->where('member.LoginID', $username)
            ->first();
    }

    /**
     * 檢查使用者密碼
     *
     * 此方法為改變 passport 預設的檢查使用者密碼邏輯
     * 請參考 \vendor\laravel\passport\src\Bridge\UserRepository.php
     *
     * @param $password
     *
     * @return bool
     */
    public function validateForPassportPasswordGrant($password)
    {
        return $this->Password == sha1($password);
    }

    /**
     * 驗證老師帳號
     *
     * @param $username
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findForTeacher($username)
    {
        return $this->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->join('schoolinfo', 'member.SchoolID', '=', 'schoolinfo.SchoolID')
            ->where('member.Status', 1)
            ->where('systemauthority.IDLevel', 'T')
            ->where('member.LoginID', $username)
            ->first();
    }

    public function Course()
    {
        return $this->hasOne(Course::class, 'MemberID');
    }

    public function Systemauthority()
    {
        return $this->hasOne(Systemauthority::class, 'MemberID');
    }

    public function Testpaper()
    {
        return $this->belongsTo(Testpaper::class, 'MemberID', 'MemberID');
    }

}
