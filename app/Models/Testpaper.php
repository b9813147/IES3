<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testpaper extends Model
{
    public $timestamps = false;
    protected $table = 'testpaper';
    protected $primaryKey = 'TPID';
    protected $fillable = [
        'MemberID',
        'TPName',
        'Subject',
        'CreateTime',
        'UpdateTime',
        'ItemCount',
        'TestCount',
        'Status',
    ];

    public function Testiem()
    {
        return $this->belongsTo(Testitem::class, 'TPID', 'TPID');
    }

    public function Member()
    {
        return $this->hasMany(Member::class, 'MemberID', 'MemberID');
    }


}
