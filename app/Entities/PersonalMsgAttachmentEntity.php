<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Supports\HashIdSupport;

/**
 * Table `personalmsg_attachment` entity
 *
 * @package App\Entities
 */
class PersonalMsgAttachmentEntity extends Model
{
    use HashIdSupport;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personalmsg_attachment';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * 完整檔案名稱含副檔名
     *
     * @return string
     */
    public function getBaseNameAttribute()
    {
        return "{$this->name}.{$this->extension}";
    }

    /**
     * 加密 msg_file_id
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        $id = $this->encodeHashId($this->msg_file_id);
        return "{$id}";
    }
}