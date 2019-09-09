<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;
use App\Exceptions\CursorPaginationException;
use App\Supports\TimeSupport;

/**
 * Class PersonalMsgRepository
 *
 * @package App\Repositories\Eloquent
 */
class PersonalMsgRepository extends BaseRepository
{
    use TimeSupport;

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\PersonalMsgEntity";
    }

    public function boot()
    {

    }

    /**
     * 依時間型分頁取得個人紙條清單
     * 預設使用 SendTime 排序，依最新建立的資料為最先顯示。
     * 當 $prev、$next 同時有值，則只會取 $next 參數。
     *
     * @param $memberID
     * @param string|null $prev
     * @param string|null $next
     * @param integer|null $limit
     * @param string $direction
     *
     * @return mixed
     *
     * @throws CursorPaginationException
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAllMessagesByTimeCursor($memberID, $prev = null, $next = null, $limit = null, $direction = 'desc')
    {
        // 排序方向
        $directionInverted = $direction ? $direction === 'desc' : false;

        // 解析 cursor
        list($prev, $next, $msgId) = $this->resolveCursor($prev, $next);

        // SQL
        $model = $this->model->select('*')
            ->where('MemberID', $memberID)
            ->where('GetStatus', '<>', 'K')
            ->when(is_null($msgId)
                , function ($query) {
                    return $query->where('SendTime', '<', $this->currentTimeString());
                }, function ($query) use ($prev, $next, $msgId, $directionInverted) {
                    if (!is_null($next)) {
                        $query->where('SendTime', $directionInverted ? '<' : '>', $next)
                            ->when($msgId, function ($query) use ($next, $msgId, $directionInverted) {
                                return $query->orWhere(function ($query) use ($next, $msgId, $directionInverted) {
                                    $query->where('SendTime', '=', $next)
                                        ->where('MsgID', $directionInverted ? '<' : '>', $msgId);
                                });
                            });
                    } else {
                        $query->where('SendTime', $directionInverted ? '>' : '<', $prev)
                            ->when($msgId, function ($query) use ($prev, $msgId, $directionInverted) {
                                return $query->orWhere(function ($query) use ($prev, $msgId, $directionInverted) {
                                    $query->where('SendTime', '=', $prev)
                                        ->where('MsgID', $directionInverted ? '>' : '<', $msgId);
                                });
                            });
                    }
                    return $query;
                })
            ->orderBy('SendTime', $directionInverted ? 'desc' : 'asc')
            ->orderBy('MsgID', $directionInverted ? 'desc' : 'asc')
            ->cursorPaginate('cursor_pagination_hash_id', $limit, ['*'])
            ->appends(request()->except(['prev_id', 'next_id']));

        $this->resetModel();

        return $model;
    }

    /**
     * 解析時間型分頁 ID
     * 當 $prev、$next 同時有值，則只會取 $next 參數。
     *
     * @param string|null $prev
     * @param string|null $next
     *
     * @return array
     *
     * @throws CursorPaginationException
     */
    public function resolveCursor($prev = null, $next = null)
    {
        $msgId = null;

        if (!is_null($next)) {
            list($next, $msgId) = $this->model->parseCursorPaginationHashId($next);
            if (empty($next) || empty($msgId)) {
                throw CursorPaginationException::invalidCursor($next);
            }
        } elseif (!is_null($prev)) {
            list($prev, $msgId) = $this->model->parseCursorPaginationHashId($prev);
            if (empty($prev) || empty($msgId)) {
                throw CursorPaginationException::invalidCursor($prev);
            }
        }

        return [$prev, $next, $msgId];
    }

    /**
     * 同一筆 Message 發送給多人
     *
     * @param $messages
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createMessageToMany($messages)
    {
        $msgId = $this->model->max('MsgID');
        $msgId = (empty($msgId)) ? 1 : $msgId + 1;

        $models = [];
        foreach ($messages as $attributes) {
            $models[] = $this->create($attributes + ['MsgID' => $msgId]);
        }

        return $this->model->newCollection($models);
    }
}