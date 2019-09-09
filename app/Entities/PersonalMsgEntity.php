<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Supports\HashIdSupport;
use App\Supports\TimeSupport;

/**
 * Table `personalmsg` entity
 *
 * @package App\Entities
 */
class PersonalMsgEntity extends Model
{
    use HashIdSupport, TimeSupport;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personalmsg';

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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'SendTime'
    ];

    /**
     * 時間型分頁 ID
     * 由 SendTime 和 MsgID 編碼而成。
     * 注意編碼順序須配合 function parseCursorPaginationHashId() 調整。
     *
     * @return string
     */
    public function getCursorPaginationHashIdAttribute()
    {
        $id = $this->encodeHashIdForCursorPagination([strtotime($this->SendTime), $this->MsgID]);
        return "{$id}";
    }

    /**
     * 解析時間型分頁 ID
     * 注意回傳陣列的參數順序，須配合 function getCursorPaginationHashIdAttribute() 調整。
     *
     * @param $cursor
     * @return array [SendTime, MsgID]
     */
    public function parseCursorPaginationHashId($cursor)
    {
        list($sendTime, $msgId) = array_pad($this->decodeHashIdForCursorPagination($cursor), 2, null);

        // 將時間參數轉換成 Y-m-d H:i:s
        $sendTime = $this->convertTimestampToDateTimeString($sendTime);

        return [$sendTime, $msgId];
    }

    /**
     * 關聯 Table `member` entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsToMember()
    {
        return $this->belongsTo('App\Entities\MemberEntity', 'SendMemberID', 'MemberID');
    }
}