<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Systemauthority extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'MemberID';
    protected $table = 'systemauthority';
    protected $fillable = [
        'IDLevel',
        'SystemManager',
        'ItembankManager',
        'CareerTitle',
        'Experiment',
        'ActiveManager',
        'ActiveManagerSec',
        'MaterialSize',
        'EzcmsSize',
        'SDate',
        'EDate',
        'Promoter',
        'serial',
        'analysis',
    ];

    public function Member()
    {
        return $this->belongsTo(Member::class, 'MemberID');
    }



}
