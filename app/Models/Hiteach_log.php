<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hiteach_log extends Model
{
    public $timestamps = false;
    protected $table = 'hiteach_log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'ACTIONTYPE',
        'LOGINID',
        'CHECKCLASSID',
        'dt',
        'log_path',
    ];

    function Member()
    {
        return $this->belongsTo(Member::class,'LOGINID','LoginID');
    }
}
