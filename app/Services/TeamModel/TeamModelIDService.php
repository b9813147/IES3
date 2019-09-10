<?php

namespace App\Services\TeamModel;

use App\Repositories\Eloquent\Oauth2MemberRepository;
use App\Repositories\Eloquent\Oauth2MemberLogsRepository;
use App\Repositories\MemberRepository;
use App\Constants\Models\OAuth2MemberConstant;
use App\Jobs\TeamModel\SendLicense;
use App\Exceptions\ResqueException;
use App\Events\SokratesAddChannel;
use App\Services\BigBlueOrderAuthorizationService;

/**
 * Team Model ID 相關 Service
 *
 * @package App\Services\TeamModel
 */
class TeamModelIDService
{
    /** @var Oauth2MemberRepository */
    protected $oauth2MemberRepository;

    /** @var Oauth2MemberRepository */
    protected $oauth2MemberLogsRepository;

    /** @var MemberRepository */
    protected $memberRepository;

    /** @var BigBlueOrderService */
    protected $bigBlueOrderAuthorizationService;

    /**
     * TeamModelIDService constructor.
     *
     * @param Oauth2MemberRepository $oauth2MemberRepository
     * @param Oauth2MemberLogsRepository $oauth2MemberLogsRepository
     * @param MemberRepository $memberRepository
     * @param BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService
     */
    public function __construct(
        Oauth2MemberRepository $oauth2MemberRepository,
        Oauth2MemberLogsRepository $oauth2MemberLogsRepository,
        MemberRepository $memberRepository,
        BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService
    )
    {
        $this->oauth2MemberRepository           = $oauth2MemberRepository;
        $this->oauth2MemberLogsRepository       = $oauth2MemberLogsRepository;
        $this->memberRepository                 = $memberRepository;
        $this->bigBlueOrderAuthorizationService = $bigBlueOrderAuthorizationService;
    }

    /**
     * Team Model ID 是否已經綁定
     *
     * @param string $teamModelId
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function isBindingByTeamModelID($teamModelId = null)
    {
        return $this->oauth2MemberRepository->isBindingByOauth2Account(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $teamModelId);
    }

    /**
     * Member ID 是否已經綁定
     *
     * @param integer $memberID
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function isBindingByMemberID($memberID)
    {
        return $this->oauth2MemberRepository->isBindingByMemberID(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $memberID);
    }

    /**
     * 取 TEAM Model ID 綁定的資料
     *
     * @param string $teamModelId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getUserByTeamModelId($teamModelId)
    {
        return $this->oauth2MemberRepository->findForUser(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $teamModelId);
    }

    /**
     * 將 Member ID 和 Team Model ID 綁定，並發送 license 給 core service
     *
     * @param integer $memberID
     * @param string $teamModelId
     *
     * @throws \App\Exceptions\RepositoryException
     * @throws \Exception
     */
    public function doBinding($memberID, $teamModelId)
    {
        $data = [
            'MemberID'       => $memberID,
            'oauth2_account' => $teamModelId,
            'sso_server'     => OAuth2MemberConstant::SSO_SERVER_TEAM_MODE
        ];

        $this->memberRepository->update($memberID, ['Status' => 1]);
        $oauthMember = $this->oauth2MemberRepository->create($data);
        $this->oauth2MemberLogsRepository->create($data + ['flag' => 1]);

        try {
            SendLicense::dispatch($oauthMember);
        } catch (\Exception $e) {
            $this->oauth2MemberRepository->deleteWhere($data);
            throw new ResqueException();
        }

        try {
            // 試用授權綁定
            $this->bigBlueOrderAuthorizationService->updateTrialAuthorizationByTeamModelIds($teamModelId);

            // 加入頻道
            event(new SokratesAddChannel($memberID, $teamModelId));
        } catch (\Exception $e) {
            // 失敗不處理
        }
    }

    /**
     * 將 Member ID 和 Team Model ID 解除綁定
     *
     * @param integer $memberId
     * @param string $teamModelId
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function unbinding($memberId, $teamModelId)
    {
        $data = [
            'MemberID'       => $memberId,
            'oauth2_account' => $teamModelId,
            'sso_server'     => OAuth2MemberConstant::SSO_SERVER_TEAM_MODE
        ];

        $this->oauth2MemberRepository->deleteWhere($data);
        $this->memberRepository->update($memberId, ['Status' => 0]);
        $this->oauth2MemberLogsRepository->create($data + ['flag' => 0]);
//        $this->memberRepository->update(['Status' => 0], $memberId);
    }
}