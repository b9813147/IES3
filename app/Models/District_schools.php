<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District_schools extends Model
{
    protected $table = 'district_schools';
    protected $primaryKey = 'district_id';
    protected $fillable = [
        'SchoolID'
    ];
}
