<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Districts extends Model
{

    protected $table = 'districts';
    protected $primaryKey = 'district_id';
    protected $fillable = [
        'district_name'
    ];
}
