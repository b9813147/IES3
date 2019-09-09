<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Api_a1_log extends Model
{
    protected $table = 'api_a1_log';
    protected $primaryKey = 'LogID';
    protected $fillable = [
        'MemberID',
        'RequestUri',
        'RequestMethod',
        'RequestHeader',
        'RequestBody',
        'ResponseHeader',
        'ResponseBody',
        'CreateTime',
    ];
}
