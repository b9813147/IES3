<?php

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\Events\StoreTeamContest2018Request;
use App\Services\EventsService;
use App\Http\Resources\Api\V1\TeamContest2018Resource;
use App\Exceptions\EventNotStartException;
use App\Exceptions\EventIsOverException;
use App\Exceptions\TeamModelIdNotExistsException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\ExceptionCodes\EventExceptionCode;

class TeamContest2018Controller extends BaseApiV1Controller
{
    /**
     * @var EventCreateService
     */
    protected $eventsService;

    public function __construct(EventsService $eventsService)
    {
        $this->eventsService = $eventsService;
    }

    /**
     * 使用者加入醍摩豆大賽
     * 當 TEAM Model ID 沒有綁定過 IES 帳號時，將會自動建立 IES 帳號並進行綁定
     *
     * @param StoreTeamContest2018Request $request
     *
     * @return TeamContest2018Resource
     */
    public function store(StoreTeamContest2018Request $request)
    {
        // 檢查是否為醍摩豆大賽專用的 client id，$request->input('oauth_client_id') 由 \App\Http\Middleware\CheckClientCredentials 寫入數值
        if ($request->input('oauth_client_id') != config('event.team_model_contest.2018.oauth_client_id')) {
            throw new AccessDeniedHttpException();
        }

        $teamModelId = $request->input('id');
        $userName = $request->input('user_name');

        try {
            // 取活動資料
            $event = $this->eventsService->getOpenEventInfo(config('event.team_model_contest.2018.event_id'));
            if (!$event) {
                throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_NOT_FOUND);
                return;
            }

            // 將使用者加入活動
            $this->eventsService->joinEventByTeamModelId(
                $event,
                config('event.team_model_contest.2018.school_id'),
                $teamModelId,
                $userName,
                true
            );

            return new TeamContest2018Resource($event);
        } catch (TeamModelIdNotExistsException $e) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_TEAM_MODEL_ID_NOT_EXISTS);
        } catch (EventNotStartException $e) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_NOT_START);
        } catch (EventIsOverException $e) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_IS_OVER);
        } catch (\Exception $e) {
            throw new HttpException(500, '');
        }
    }
}