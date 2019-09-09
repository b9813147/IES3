<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolInfo extends Model
{
    protected $table = 'schoolinfo';
    protected $primaryKey = 'SchoolID';
    protected $fillable = [
        'SchoolName',
        'SchoolCode',
        'Address',
        'Telephone',
        'Homepage',
        'LogoImg',
        'Status',

    ];

}
