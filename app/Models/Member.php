<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Passport\HasApiTokens;

class Member extends Model
{
    use HasApiTokens, Authenticatable, Authorizable;

    public $timestamps = false;
    protected $table = 'member';
    protected $primaryKey = 'MemberID';
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
