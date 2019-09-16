<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_outline extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $table = 'fc_outline';
    protected $fillable = [
        'fc_event_id',
        'MemberID',
        'SourceID',
        'publicflag',

    ];

}
