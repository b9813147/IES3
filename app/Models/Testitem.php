<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testitem extends Model
{
    public $timestamps = false;
    protected $table = 'testitem';
    protected $primaryKey = 'ItemNO';
    protected $fillable = [
        'TPID',
        'ItemIndex',
        'Point',
        'SpendTime',
    ];

    public function Iteminfo()
    {
        return $this->belongsTo(Iteminfo::class, 'ItemNO');
    }

    public function Testpaper()
    {
        return $this->hasMany(Testpaper::class, 'TPID', 'TPID');
    }
}
