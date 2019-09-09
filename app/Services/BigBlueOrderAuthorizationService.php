<?php

namespace App\Services;

use App\Repositories\Eloquent\SchoolInfoRepository;
use App\Repositories\Eloquent\BbPeriodsRepository;
use App\Repositories\Eloquent\BbPeriodOrdersRepository;
use App\Repositories\Eloquent\BbPeriodOrderUsersRepository;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\SystemAuthorityRepository;
use App\Repositories\Eloquent\Oauth2MemberRepository;
use App\Constants\Models\SystemAuthorityConstant;
use App\Exceptions\SchoolNotFoundException;

/**
 * Big Blue 訂單授權相關 Service
 *
 * @package App\Services
 */
class BigBlueOrderAuthorizationService
{
    /** @var SchoolInfoRepository */
    protected $schoolInfoRepository;

    /** @var BbPeriodsRepository */
    protected $bbPeriodsRepository;

    /** @var BbPeriodOrdersRepository */
    protected $bbPeriodOrdersRepository;

    /** @var BbPeriodOrderUsersRepository */
    protected $bbPeriodOrderUsersRepository;

    /** @var MemberRepository */
    protected $memberRepository;

    /** @var SystemAuthorityRepository */
    protected $systemAuthorityRepository;

    /** @var Oauth2MemberRepository */
    protected $oauth2MemberRepository;

    /**
     * BigBlueOrderService constructor.
     *
     * @param SchoolInfoRepository $schoolInfoRepository
     * @param BbPeriodsRepository $bbPeriodsRepository
     * @param BbPeriodOrdersRepository $bbPeriodOrdersRepository
     * @param BbPeriodOrderUsersRepository $bbPeriodOrderUsersRepository
     * @param MemberRepository $memberRepository
     * @param SystemAuthorityRepository $systemAuthorityRepository
     * @param Oauth2MemberRepository $oauth2MemberRepository
     */
    public function __construct(
        SchoolInfoRepository $schoolInfoRepository,
        BbPeriodsRepository $bbPeriodsRepository,
        BbPeriodOrdersRepository $bbPeriodOrdersRepository,
        BbPeriodOrderUsersRepository $bbPeriodOrderUsersRepository,
        MemberRepository $memberRepository,
        SystemAuthorityRepository $systemAuthorityRepository,
        Oauth2MemberRepository $oauth2MemberRepository
    )
    {
        $this->schoolInfoRepository = $schoolInfoRepository;
        $this->bbPeriodsRepository = $bbPeriodsRepository;
        $this->bbPeriodOrdersRepository = $bbPeriodOrdersRepository;
        $this->bbPeriodOrderUsersRepository = $bbPeriodOrderUsersRepository;
        $this->memberRepository = $memberRepository;
        $this->systemAuthorityRepository = $systemAuthorityRepository;
        $this->oauth2MemberRepository = $oauth2MemberRepository;
    }

    /**
     * 依學校代碼更新授權
     *
     * @param $schoolCode
     *
     * @return mixed
     *
     * @throws SchoolNotFoundException
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateSalesBySchoolCode($schoolCode)
    {
        if (empty($schoolCode)) {
            throw new \InvalidArgumentException();
        }

        // 取得學校
        $school = $this->schoolInfoRepository->getSchoolByCodeOrAbbr($schoolCode, null);
        if (!$school) {
            throw new SchoolNotFoundException();
        }

        // 取得學校所有審核狀態為通過的銷售授權訂單，並且訂單是在有效時間內
        $orders = $this->bbPeriodOrdersRepository->getOrdersBySchoolId($school->SchoolID);

        // 更新學校銷售授權
        return $this->updateSchoolSalesAuthorization($school->SchoolID, $orders);
    }

    /**
     * 更新所有學校銷售授權
     *
     * @return array|\Illuminate\Support\Collection
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateAllSchoolSalesAuthorization()
    {
        // 有授權的學校
        $authorizationSchools = [];

        // 取得學校所有審核狀態為通過的銷售授權訂單，並且訂單是在有效時間內
        $allSchoolOrders = $this->bbPeriodOrdersRepository->getAllSchoolOrders();

        // 有訂單
        if ($allSchoolOrders->isNotEmpty()) {
            $grouped = $allSchoolOrders->groupBy('belongsToBbPeriods.SchoolID');

            // 更新學校銷售授權
            $grouped->each(function ($item, $key) use (&$authorizationSchools) {
                $authorizationSchools[] = $this->updateSchoolSalesAuthorization($key, $item);
            });
        }

        $authorizationSchools = collect($authorizationSchools);

        // 取得所有已擁有銷售授權的學校
        $schools = $this->schoolInfoRepository->findWhere(['AuthorizationType' => 1]);

        // 將已過期的學校移除授權
        if ($schools->isNotEmpty()) {
            $schools = $schools->pluck('SchoolID')->diff($authorizationSchools->pluck('SchoolID'));
            $schools->each(function ($item, $key) {
                $this->updateSchoolSalesAuthorization($item, collect());
            });
        }

        return $authorizationSchools;
    }

    /**
     * 依 TEAM Model ID 更新試用授權
     *
     * @param array $teamModelIds TEAM Model ID
     *
     * @return array
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateTrialAuthorizationByTeamModelIds($teamModelIds)
    {
        // 有更新的使用者
        $updateUsers = [];

        if (is_string($teamModelIds)) {
            $teamModelIds = (array)$teamModelIds;
        }

        if (!is_array($teamModelIds)) {
            throw new \InvalidArgumentException();
        }

        // 取得已綁定的 IES 帳號
        $users = $this->oauth2MemberRepository->findWhereIn('oauth2_account', $teamModelIds);

        // 有綁定的 IES 帳號
        if ($users->isNotEmpty()) {

            // 取得使用者的試用授權訂單，並且訂單是在有效時間內及審核狀態為通過
            $orders = $this->bbPeriodOrderUsersRepository->getTrialOrdersByTeamModelIds($teamModelIds);

            // 更新授權
            $users->each(function ($item, $key) use ($orders, &$updateUsers) {
                $search = $orders->where('team_model_id', $item->oauth2_account)->first();

                // 沒有訂單則移除試用授權
                // if (!$search) {
                //     $this->updateUserTrialAuthorizationToDefault($item->MemberID);
                //     return true;
                // }

                // 有訂單
                $this->updateUserTrialAuthorization($item->MemberID, $search->socratic_video, $search->socratic_hi_encoder, $search->socratic_report, $search->storage);

                $updateUsers[] = $item;
            });
        }

        return $updateUsers;
    }

    /**
     * 更新所有試用授權
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateAllTrialAuthorization()
    {
        // 有授權的使用者
        $authorizationUsers = [];

        // 取得所有試用授權訂單，並且訂單是在有效時間內及審核狀態為通過
        $allTrialOrders = $this->bbPeriodOrderUsersRepository->getAllTrialOrders();

        // 有訂單
        if ($allTrialOrders->isNotEmpty()) {

            // 取得已綁定的 IES 帳號
            $users = $this->oauth2MemberRepository->findWhereIn('oauth2_account', $allTrialOrders->pluck('team_model_id')->all());

            // 有綁定的 IES 帳號
            if ($users->isNotEmpty()) {

                // 更新授權
                $users->each(function ($item, $key) use ($allTrialOrders, &$authorizationUsers) {
                    $search = $allTrialOrders->where('team_model_id', $item->oauth2_account)->first();

                    // 有訂單
                    $this->updateUserTrialAuthorization($item->MemberID, $search->socratic_video, $search->socratic_hi_encoder, $search->socratic_report, $search->storage);

                    $authorizationUsers[] = $item;
                });
            }
        }

        $authorizationUsers = collect($authorizationUsers);

        // 取得所有已擁有試用授權的使用者
        $users = $this->systemAuthorityRepository->findWhere(['authorization_type' => 1]);

        // 將已過期的使用者移除授權
        if ($users->isNotEmpty()) {
            $users = $users->pluck('MemberID')->diff($authorizationUsers->pluck('MemberID'));

            $users->each(function ($item, $key) {
                $this->updateUserTrialAuthorizationToDefault($item);
            });
        }

        return $authorizationUsers;
    }

    /**
     * 更新學校銷售授權
     *
     * @param integer $schoolId
     * @param \App\Entities\BbPeriodOrdersEntity $orders
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function updateSchoolSalesAuthorization($schoolId, $orders)
    {
        // 學校授權預設值
        $maxStorage = 0;
        $maxSocraticVideo = 0;
        $maxSocraticHiEncoder = 0;
        $maxSocraticReport = 0;
        $maxSocraticDashboard = 0;
        $maxAclassone = 0;
        $authorizationType = 0;

        // 學校有訂單授權
        if ($orders->isNotEmpty()) {
            $maxStorage = $orders->sum('storage');
            $maxSocraticVideo = $orders->sum('socratic_video');
            $maxSocraticHiEncoder = $orders->sum('socratic_hi_encoder');
            $maxSocraticReport = $orders->sum('socratic_report');
            $maxSocraticDashboard = $orders->sum('socratic_dashboard');
            $maxAclassone = $orders->sum('aclass_one');
            $authorizationType = 1;
        }

        // 將整理後的加值參數填入 SchoolInfo
        $newSchool = $this->schoolInfoRepository->update([
            'MaxStorage' => $maxStorage,
            'MaxSocraticVideo' => $maxSocraticVideo,
            'MaxSocraticHiEncoder' => $maxSocraticHiEncoder,
            'MaxSocraticReport' => $maxSocraticReport,
            'MaxSocraticDashboard' => $maxSocraticDashboard,
            'MaxAclassone' => $maxAclassone,
            'AuthorizationType' => $authorizationType
        ], $schoolId);

        // 更新學校的老師授權
        $this->resetTeacherSalesAuthorization($newSchool);

        return $newSchool;
    }

    /**
     * 更新學校老師授權
     *
     * @param \App\Entities\SchoolInfoEntity $school
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function resetTeacherSalesAuthorization($school)
    {
        if (!$school instanceof \App\Entities\SchoolInfoEntity) {
            throw new \InvalidArgumentException();
        }

        // 取所有銷售授權的老師資料
        $teacher = $this->memberRepository->findAllSalesTeacherInSchool($school->SchoolID);
        if ($teacher->isEmpty()) {
            return;
        }

        // 如果已配發的值大於學校的授權，則將所有老師還原為預設
        if ($teacher->sum('EzcmsSize') > $school->MaxStorage) {
            $this->systemAuthorityRepository->updateTeacherEzCmsSizeToDefault($school->SchoolID);
        }

        // 整理蘇格拉底授權
        $grouped = $teacher->groupBy('analysis');

        // 哪些蘇格拉底授權要更新
        $whereAnalysis = null;

        // 蘇格拉底授權報告，如果已配發的值大於學校的授權，則將所有老師還原為預設
        if (isset($grouped[SystemAuthorityConstant::ANALYSIS_REPORT]) &&
            count($grouped[SystemAuthorityConstant::ANALYSIS_REPORT]) > $school->MaxSocraticReport
        ) {
            $whereAnalysis[] = SystemAuthorityConstant::ANALYSIS_REPORT;
        }

        // 蘇格拉底授權桌面，如果已配發的值大於學校的授權，則將所有老師還原為預設
        if (isset($grouped[SystemAuthorityConstant::ANALYSIS_HI_ENCODER]) &&
            count($grouped[SystemAuthorityConstant::ANALYSIS_HI_ENCODER]) > $school->MaxSocraticHiEncoder
        ) {
            $whereAnalysis[] = SystemAuthorityConstant::ANALYSIS_HI_ENCODER;
        }

        // 蘇格拉底授權影片，如果已配發的值大於學校的授權，則將所有老師還原為預設
        if (isset($grouped[SystemAuthorityConstant::ANALYSIS_VIDEO]) &&
            count($grouped[SystemAuthorityConstant::ANALYSIS_VIDEO]) > $school->MaxSocraticVideo
        ) {
            $whereAnalysis[] = SystemAuthorityConstant::ANALYSIS_VIDEO;
        }

        // 更新蘇格拉底授權
        if ($whereAnalysis) {
            $this->systemAuthorityRepository->updateTeacherAnalysisToDefault($school->SchoolID, $whereAnalysis);
        }
    }

    /**
     * 移除使用者試用授權
     *
     * @param $memberId
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function updateUserTrialAuthorizationToDefault($memberId)
    {
        $this->systemAuthorityRepository->update([
            'EzcmsSize' => 0,
            'Analysis' => 0,
            'authorization_type' => 0
        ], $memberId);
    }

    /**
     * 更新使用者試用授權
     *
     * @param integer $memberId
     * @param integer $socraticVideo
     * @param integer $socraticHiEncoder
     * @param integer $socraticReport
     * @param integer $storage
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function updateUserTrialAuthorization($memberId, $socraticVideo = 0, $socraticHiEncoder = 0, $socraticReport = 0, $storage = 0)
    {
        // 蘇格拉底授權
        $analysis = SystemAuthorityConstant::ANALYSIS_NO_AUTHORIZATION;
        if ($socraticVideo > 0) {
            $analysis = SystemAuthorityConstant::ANALYSIS_VIDEO;
        } elseif ($socraticHiEncoder > 0) {
            $analysis = SystemAuthorityConstant::ANALYSIS_HI_ENCODER;
        } elseif ($socraticReport > 0) {
            $analysis = SystemAuthorityConstant::ANALYSIS_REPORT;
        }

        // 更新授權
        $this->systemAuthorityRepository->update([
            'EzcmsSize' => $storage,
            'Analysis' => $analysis,
            'authorization_type' => 1
        ], $memberId);
    }
}