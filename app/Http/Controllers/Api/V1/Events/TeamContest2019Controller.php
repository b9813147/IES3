<?php

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\Events\StoreTeamContest2019Request;
use App\Http\Resources\Api\V1\TeamContest2019Resource;
use App\Services\EventsService;
use App\Exceptions\EventNotStartException;
use App\Exceptions\EventIsOverException;
use App\Exceptions\SchoolCreateException;
use App\Exceptions\SokratesSendAddChannelException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\ExceptionCodes\EventExceptionCode;
use App\ExceptionCodes\SchoolExceptionCode;
use App\ExceptionCodes\SokratesExceptionCode;
use App\Events\SokratesAddChannel;

class TeamContest2019Controller extends BaseApiV1Controller
{
    /**
     * @var EventsService
     */
    protected $eventsService;

    /**
     * TeamContest2019Controller constructor.
     *
     * @param EventsService $eventsService
     */
    public function __construct(EventsService $eventsService)
    {
        $this->eventsService = $eventsService;
    }

    /**
     * 使用者加入醍摩豆大賽
     * 當 TEAM Model ID 沒有綁定過 IES 帳號時，將會自動建立 IES 帳號和學校並進行綁定
     *
     * @param StoreTeamContest2019Request $request
     *
     * @return TeamContest2019Resource
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function store(StoreTeamContest2019Request $request)
    {
        // 檢查是否為醍摩豆大賽專用的 client id，$request->input('oauth_client_id') 由 \App\Http\Middleware\CheckClientCredentials 寫入數值
        if (!in_array($request->input('oauth_client_id'), config('event.team_model_contest.2019.oauth_client_id'))) {
            throw new AccessDeniedHttpException();
        }

        $teamModelId = $request->input('id');
        $userName = $request->input('user_name');
        $schoolCode = $request->input('school_code');
        $schoolAbbr = $request->input('school_abbr');
        $schoolName = $request->input('school_name');

        // 取活動資料
        try {
            $event = $this->eventsService->getOpenEventInfo(config('event.team_model_contest.2019.event_id'));
        } catch (EventNotStartException $e) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_NOT_START);
        } catch (EventIsOverException $e) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_IS_OVER);
        } catch (\Exception $e) {
            throw new HttpException(500, '', null, [], EventExceptionCode::EVENT_GET_ERROR);
        }

        // 沒有活動資料
        if (!$event) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_NOT_FOUND);
        }

        // 將使用者加入活動
        try {
            $user = $this->eventsService->joinEventByTeamModelIdAndSchool(
                $event, $teamModelId, $userName, $schoolCode, $schoolAbbr, $schoolName, '2019-08-31'
            );
        } catch (SchoolCreateException $e) {
            throw new ResourceException('', null, null, [], SchoolExceptionCode::SCHOOL_CREATE_ERROR);
        } catch (\Exception $e) {
            throw new HttpException(500, '', null, [], EventExceptionCode::EVENT_ADD_USER_ERROR);
        }

        // 加入蘇格拉底頻道
        try {
            event(new SokratesAddChannel($user->MemberID, $teamModelId, '2019ISLA'));
        } catch (SokratesSendAddChannelException $e) {
            $this->eventsService->deleteJoinEventByMemberId($event->event_id, $user->MemberID);
            throw new ResourceException('', null, null, [], SokratesExceptionCode::SOKRATES_ADD_CHANNEL_ERROR);
        } catch (\Exception $e) {
            $this->eventsService->deleteJoinEventByMemberId($event->event_id, $user->MemberID);
            throw new HttpException(500, '', null, [], SokratesExceptionCode::SOKRATES_SEND_ERROR);
        }

        return new TeamContest2019Resource($event);
    }
}