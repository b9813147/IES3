<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classinfo extends Model
{
    protected $table = 'classinfo';
    protected $primaryKey = 'CNO';
    protected $fillable = [
        'Department', 'GradeName', 'ClassName',
    ];
}
