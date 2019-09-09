<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classpower extends Model
{

    public $timestamps = false;
    protected $table = 'classpower';
    protected $primaryKey = 'ser';
    protected $fillable = [
        'AcademicYear',
        'Sorder',
        'classtype',
        'ClassID',
        'powertype',
        'MemberID',
    ];

}
