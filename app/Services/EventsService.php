<?php

namespace App\Services;

use App\Repositories\Eloquent\EventsRepository;
use App\Repositories\Eloquent\EventUsersRepository;
use App\Repositories\Eloquent\Oauth2MemberRepository;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\SchoolInfoRepository;
use App\Services\TeamModel\TeamModelIDService;
use App\Constants\Models\OAuth2MemberConstant;
use App\Supports\TimeSupport;
use App\Exceptions\EventNotStartException;
use App\Exceptions\EventIsOverException;
use App\Exceptions\TeamModelIdNotExistsException;
use App\Exceptions\SchoolCreateException;

/**
 * 查詢活動相關 Service
 *
 * @package App\Services
 */
class EventsService
{
    use TimeSupport;

    /** @var EventsRepository */
    protected $eventsRepository;

    /** @var EventUsersRepository */
    protected $eventUsersRepository;

    /** @var Oauth2MemberRepository */
    protected $oauth2MemberRepository;

    /** @var MemberRepository */
    protected $memberRepository;

    /** @var SchoolInfoRepository */
    protected $schoolInfoRepository;

    /** @var TeamModelIDService */
    protected $teamModelIDService;

    /** @var UserCreateService */
    protected $userCreateService;

    /** @var SchoolCreateService */
    protected $schoolCreateService;

    /**
     * EventsService constructor.
     *
     * @param EventsRepository $eventsRepository
     * @param EventUsersRepository $eventUsersRepository
     * @param Oauth2MemberRepository $oauth2MemberRepository
     * @param MemberRepository $memberRepository
     * @param SchoolInfoRepository $schoolInfoRepository
     * @param TeamModelIDService $teamModelIDService
     * @param UserCreateService $userCreateService
     * @param SchoolCreateService $schoolCreateService
     */
    public function __construct(
        EventsRepository $eventsRepository,
        EventUsersRepository $eventUsersRepository,
        Oauth2MemberRepository $oauth2MemberRepository,
        MemberRepository $memberRepository,
        SchoolInfoRepository $schoolInfoRepository,
        TeamModelIDService $teamModelIDService,
        UserCreateService $userCreateService,
        SchoolCreateService $schoolCreateService
    )
    {
        $this->eventsRepository = $eventsRepository;
        $this->eventUsersRepository = $eventUsersRepository;
        $this->oauth2MemberRepository = $oauth2MemberRepository;
        $this->memberRepository = $memberRepository;
        $this->schoolInfoRepository = $schoolInfoRepository;
        $this->teamModelIDService = $teamModelIDService;
        $this->userCreateService = $userCreateService;
        $this->schoolCreateService = $schoolCreateService;
    }

    /**
     * 查詢進行中的活動資料
     *
     * @param integer $eventId 活動 ID
     *
     * @return mixed
     *
     * @throws EventIsOverException
     * @throws EventNotStartException
     * @throws \App\Exceptions\RepositoryException
     */
    public function getOpenEventInfo($eventId)
    {
        // 取活動資料
        $event = $this->eventsRepository->find($eventId);

        if (!$event) {
            return null;
        }

        // 當前系統時間
        $currentTime = $this->currentTimeString();

        // 活動還沒開始
        if ($event->start_at > $currentTime) {
            throw new EventNotStartException();
        }

        // 活動已經結束
        if ($event->end_at < $currentTime) {
            throw new EventIsOverException();
        }

        return $event;
    }

    /**
     * 將使用者加入活動
     *
     * @param EventsRepository $event
     * @param integer $schoolId
     * @param string $teamModelId
     * @param string $userName
     * @param bool $autoCreateUser
     *
     * @return mixed
     *
     * @throws TeamModelIdNotExistsException
     * @throws \App\Exceptions\RepositoryException
     */
    public function joinEventByTeamModelId($event, $schoolId, $teamModelId, $userName, $autoCreateUser = false)
    {
        $user = $this->oauth2MemberRepository->findForUser(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $teamModelId);

        // Team Model ID 有綁定 IES 帳號
        if ($user) {
            // 參與活動
            return $this->joinEventByMemberId($event->event_id, $user->MemberID);
        }

        // 不自動建立帳號
        if (!$autoCreateUser) {
            throw new TeamModelIdNotExistsException();
        }

        // 建立 IES 帳號
        $user = $this->userCreateService->createUserForTeacher(
            [
                'real_name' => $userName,
                'school_id' => $schoolId,
                'status' => 1
            ],
            [
                'analysis' => 1,
                's_date' => $event->start_at,
                'e_date' => $event->end_at,
            ]
        );

        // IES 帳號綁定 TEAM Model ID
        $this->teamModelIDService->doBinding($user->MemberID, $teamModelId);

        // 參與活動
        return $this->joinEventByMemberId($event->event_id, $user->MemberID);
    }

    /**
     * 將使用者加入活動
     *
     * @param EventsRepository $event
     * @param string $teamModelId TEAM Model ID
     * @param string $userName 使用者姓名
     * @param string $schoolCode 學校代號
     * @param string $schoolAbbr 學校簡碼
     * @param string $schoolName 學校名稱
     * @param string $schoolEndDate 學校授權到期日
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     * @throws \App\Exceptions\SchoolCreateException
     */
    public function joinEventByTeamModelIdAndSchool($event, $teamModelId, $userName, $schoolCode, $schoolAbbr, $schoolName, $schoolEndDate)
    {
        $user = $this->oauth2MemberRepository->findForUser(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $teamModelId);
        if ($user) {
            $user = $this->memberRepository->findForUser($user->MemberID);
        }

        // Team Model ID 沒有有綁定 IES 帳號
        if (!$user) {
            // 取出或建立學校資料
            $school = $this->createSchoolAndRoot($schoolCode, $schoolAbbr, $schoolName, $schoolEndDate);

            // 建立 IES 帳號
            $user = $this->userCreateService->createUserForTeacher(
                [
                    'real_name' => $userName,
                    'school_id' => $school->SchoolID,
                    'status' => 1
                ],
                [
                    'analysis' => 3,
                    's_date' => '0000-00-00',
                    'e_date' => '0000-00-00',
                    'ezcms_size' => 10240,
                ]
            );

            // IES 帳號綁定 TEAM Model ID
            $this->teamModelIDService->doBinding($user->MemberID, $teamModelId);
        }

        // 參與活動
        $this->joinEventByMemberId($event->event_id, $user->MemberID);

        return $user;
    }

    /**
     * 建立學校和管理員
     *
     * @param string $schoolCode 學校代碼
     * @param string $schoolAbbr 學校簡碼
     * @param string $schoolName 學校名稱
     * @param string $schoolEndDate 學校授權到期日
     *
     * @return mixed
     *
     * @throws SchoolCreateException
     * @throws \App\Exceptions\RepositoryException
     */
    public function createSchoolAndRoot($schoolCode, $schoolAbbr, $schoolName, $schoolEndDate)
    {
        // 取出學校資料
        $school = $this->schoolInfoRepository->getSchoolByCodeOrAbbr($schoolCode, $schoolAbbr);

        // 沒有則建立學校資料
        if (is_null($school)) {
            $school = $this->schoolCreateService->createSchool(
                $schoolCode,
                $schoolAbbr,
                [
                    'school_name' => $schoolName,
                    'end_date' => $schoolEndDate,
                    'max_teacher' => 300,
                    'max_aclassone' => 0
                ]
            );

            if (empty($school)) {
                throw new SchoolCreateException();
            }

            // 建立管理員
            $this->userCreateService->createUserForRootBySchoolAbbr(
                $school->Abbr,
                [
                    'real_name' => $school->SchoolName,
                    'school_id' => $school->SchoolID,
                    'status' => 1
                ],
                [
                    'analysis' => 0,
                    's_date' => '0000-00-00',
                    'e_date' => '0000-00-00',
                ]
            );
        }

        return $school;
    }

    /**
     * 將使用者加入活動
     *
     * @param integer $eventId
     * @param integer $memberId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function joinEventByMemberId($eventId, $memberId)
    {
        return $this->eventUsersRepository->firstOrCreate(['event_id' => $eventId, 'MemberID' => $memberId]);
    }

    /**
     * 將使用者移出活動
     *
     * @param integer $eventId
     * @param integer $memberId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function deleteJoinEventByMemberId($eventId, $memberId)
    {
        return $this->eventUsersRepository->deleteWhere(['event_id' => $eventId, 'MemberID' => $memberId]);
    }
}
