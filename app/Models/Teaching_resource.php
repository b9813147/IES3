<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teaching_resource extends Model
{
    protected $primaryKey = 'tr_id';
    protected $table = 'teaching_resource';
    protected $fillable = [
        'MemberID',
        'SchoolID',
        'encodedName',
        'fileName',
        'extension',
        'type',
        'size',
        'description',
        'sharedLevel',
    ];


}
