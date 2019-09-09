<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semesterinfo extends Model
{
    protected $table = 'semesterinfo';
    protected $primaryKey = 'SNO';
    protected $fillable = [
        'AcademicYear', 'SOrder', 'SchoolID',
    ];

    public function Course()
    {
        return $this->hasMany('App\Models\Course','SNO');
    }
}
