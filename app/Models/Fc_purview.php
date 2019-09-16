<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fc_purview extends Model
{
    public $timestamps = false;
    protected $primaryKey = false;
    protected $table = 'fc_purview';

    protected $fillable = [
        'MemberID',
        'fc_outline_id',
        'purview',
    ];
}
