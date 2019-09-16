<?php

namespace App\Services\TeamModel;

use App\Models\Classpower;
use App\Models\Course;
use App\Models\Fc_outline;
use App\Models\Fc_purview;
use App\Models\Member;
use App\Repositories\ClassPowerRepository;
use App\Repositories\CourseRepository;
use App\Repositories\Eloquent\Oauth2MemberRepository;
use App\Repositories\Eloquent\Oauth2MemberLogsRepository;
use App\Repositories\Fc_outlineRepository;
use App\Repositories\Fc_purviewRepository;
use App\Repositories\MemberRepository;
use App\Constants\Models\OAuth2MemberConstant;
use App\Jobs\TeamModel\SendLicense;
use App\Exceptions\ResqueException;
use App\Events\SokratesAddChannel;
use App\Services\BigBlueOrderAuthorizationService;
use App\Services\SemesterService;

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

    /** @var Fc_outlineRepository */
    protected $fc_outlineRepository;

    /** @var Fc_purviewRepository */
    protected $fc_purviewRepository;

    /** @var CourseRepository */
    protected $coursesRepository;

    /** @var ClassPowerRepository */
    protected $classPowerRepository;

    /** @var BigBlueOrderService */
    protected $bigBlueOrderAuthorizationService;

    protected $semesterService;

    /**
     * TeamModelIDService constructor.
     *
     * @param Oauth2MemberRepository $oauth2MemberRepository
     * @param Oauth2MemberLogsRepository $oauth2MemberLogsRepository
     * @param MemberRepository $memberRepository
     * @param BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService
     * @param SemesterService $semesterService
     * @param Fc_purviewRepository $fc_purviewRepository
     * @param Fc_outlineRepository $fc_outlineRepository
     * @param CourseRepository $coursesRepository
     * @param ClassPowerRepository $classPowerRepository
     */
    public function __construct(
        Oauth2MemberRepository $oauth2MemberRepository,
        Oauth2MemberLogsRepository $oauth2MemberLogsRepository,
        MemberRepository $memberRepository,
        BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService,
        SemesterService $semesterService,
        Fc_purviewRepository $fc_purviewRepository,
        Fc_outlineRepository $fc_outlineRepository,
        CourseRepository $coursesRepository,
        ClassPowerRepository $classPowerRepository
    )
    {
        $this->oauth2MemberRepository           = $oauth2MemberRepository;
        $this->oauth2MemberLogsRepository       = $oauth2MemberLogsRepository;
        $this->memberRepository                 = $memberRepository;
        $this->bigBlueOrderAuthorizationService = $bigBlueOrderAuthorizationService;
        $this->semesterService                  = $semesterService;
        $this->fc_outlineRepository             = $fc_outlineRepository;
        $this->fc_purviewRepository             = $fc_purviewRepository;
        $this->coursesRepository                = $coursesRepository;
        $this->classPowerRepository             = $classPowerRepository;
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

        // 寫法待優化

        // 取出公開課綱
        $outline = $this->fc_outlineRepository->getPublicOutlineId();

        // 分享名單
        $preview = $this->fc_purviewRepository->getFcPurviewId($memberID);


        // 檢查 是否有被加入分享過
        if ($preview->isEmpty()) {
            $outline->each(function ($id) use ($memberID) {
                $this->fc_purviewRepository->AddPurview($memberID, $id);
            });

        } else if ($outline->diff($preview)->isNotEmpty()) {
            // 尚未被加入的課綱
            $outline->diff($preview)->each(function ($id) use ($memberID) {
                $this->fc_purviewRepository->AddPurview($memberID, $id);
            });
        }

        // 加入示範課程
        $member = $this->memberRepository->findBy('MemberID', $memberID);

        $semester = $this->semesterService->getCurrentSemester();
        // 老師課程
        $getMemberCourseBySNO = $this->coursesRepository->findWhere(['MemberID' => $memberID, 'SNO' => $semester->SNO]);
        // 協同分享課程
        $getSharedCourses = $this->coursesRepository->getSharedCoursesByCourseNO($member->MemberID, $member->SchoolID);

        // 協同老師
        $member_ClassPower = $this->classPowerRepository->getByTeacherClassID($member->MemberID);

        //  判斷有無分享課程  及 這個老師 沒有這學期的課程
        if ($getMemberCourseBySNO->isEmpty() && !$getSharedCourses->isEmpty()) {
            // 判斷協同老師有被加入過
            if ($member_ClassPower->isEmpty()) {
                $getSharedCourses->each(function ($courseNO) use ($semester, $member) {
                    $this->classPowerRepository->add($semester->AcademicYear, $semester->SOrder, $courseNO, $member->MemberID);
                });
            } else if ($getSharedCourses->diff($member_ClassPower)->isNotEmpty()) {
                $getSharedCourses->diff($member_ClassPower)->each(function ($courseNO) use ($semester, $member) {
                    $this->classPowerRepository->add($semester->AcademicYear, $semester->SOrder, $courseNO, $member->MemberID);
                });
            }

        }

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
//            $this->bigBlueOrderAuthorizationService->updateTrialAuthorizationByTeamModelIds($teamModelId);

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
        $this->oauth2MemberLogsRepository->create($data + ['flag' => 0]);
        // 找出當前學校示範課程
        $member           = $this->memberRepository->findBy('MemberID', $memberId);
        $getSharedCourses = $this->coursesRepository->getSharedCoursesByCourseNO($memberId, $member->SchoolID);
        // 刪除示範課程
        $this->classPowerRepository->deleteByClassNO($getSharedCourses);

        if ($this->memberRepository->findWhere(['MemberID'=> $memberId,'LoginID'=> $teamModelId])->isNotEmpty()) {
            $this->memberRepository->update($memberId, ['Status' => 0]);
        }

    }
}