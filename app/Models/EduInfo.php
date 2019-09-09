<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EduInfo extends Model
{
    public $timestamps = false;
    protected $connection = 'mysql_publicItem';
    protected $table = 'eduinfo';
    protected $primaryKey = 'EduID';
    protected $fillable = [
        'EduName',
        'QCount',
        'TPCount',
        'IsClassify',
        'SchoolID',
    ];
}
