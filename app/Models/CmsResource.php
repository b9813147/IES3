<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsResource extends Model
{
    protected $table = 'cms_resource';
    protected $primaryKey = 'rid';
    protected $fillable = [
        'rtype',
        'stype',
        'rname',
        'file_name',
        'rextension',
        'owner',
        'member_id',
        'rauthor',
        'rcopyright',
        'rdescription',
        'created_dt',
        'updated_dt',
        'flag',
        'rthumbnailPath',
        'rduration',
        'msr_videoId',
        'msr_video_list',
    ];

    public function Member()
    {
        return $this->belongsTo(Member::class,'member_id','MemberID');
    }
}
