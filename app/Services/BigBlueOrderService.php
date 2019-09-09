<?php

namespace App\Services;

use App\Repositories\Eloquent\SchoolInfoRepository;
use App\Repositories\Eloquent\BbPeriodsRepository;
use App\Repositories\Eloquent\BbPeriodOrdersRepository;
use App\Repositories\Eloquent\BbPeriodOrderUsersRepository;
use App\Exceptions\SchoolCreateException;

/**
 * Big Blue 訂單相關 Service
 *
 * @package App\Services
 */
class BigBlueOrderService
{
    /** @var SchoolInfoRepository */
    protected $schoolInfoRepository;

    /** @var BbPeriodsRepository */
    protected $bbPeriodsRepository;

    /** @var BbPeriodOrdersRepository */
    protected $bbPeriodOrdersRepository;

    /** @var BbPeriodOrderUsersRepository */
    protected $bbPeriodOrderUsersRepository;

    /** @var string 虛擬學校代碼 */
    private $schoolCode;

    /** @var string 虛擬學校名稱 */
    private $schoolName;

    /** @var string 虛擬學校簡碼 */
    private $schoolShortCode;

    /** @var string 訂單單號 */
    private $orderId;

    /** @var integer 訂單審核狀態 */
    private $orderAudit;

    /** @var array 醍魔豆帳號 */
    private $teamModelIds;

    /** @var integer 授權期數 ID */
    private $periodId;

    /** @var integer 授權類型 */
    private $periodType;

    /** @var string 授權起始日期 */
    private $startDate;

    /** @var string 授權終止日期 */
    private $endDate;

    /** @var null 蘇格拉底影片 */
    private $socraticVideo = null;

    /** @var null 蘇格拉底桌面 */
    private $socraticHiEncoder = null;

    /** @var null 蘇格拉底報告 */
    private $socraticReport = null;

    /** @var null 智慧教學服務空間 */
    private $storage = null;

    /** @var null 智慧教學服務空間 */
    private $socraticDashboard = null;

    /** @var null AClassONE */
    private $aClassOne = null;

    /** @var UserCreateService */
    protected $userCreateService;

    /** @var SchoolCreateService */
    protected $schoolCreateService;

    /**
     * BigBlueOrderService constructor.
     *
     * @param SchoolInfoRepository $schoolInfoRepository
     * @param BbPeriodsRepository $bbPeriodsRepository
     * @param BbPeriodOrdersRepository $bbPeriodOrdersRepository
     * @param BbPeriodOrderUsersRepository $bbPeriodOrderUsersRepository
     * @param UserCreateService $userCreateService
     * @param SchoolCreateService $schoolCreateService
     */
    public function __construct(
        SchoolInfoRepository $schoolInfoRepository,
        BbPeriodsRepository $bbPeriodsRepository,
        BbPeriodOrdersRepository $bbPeriodOrdersRepository,
        BbPeriodOrderUsersRepository $bbPeriodOrderUsersRepository,
        UserCreateService $userCreateService,
        SchoolCreateService $schoolCreateService
    )
    {
        $this->schoolInfoRepository = $schoolInfoRepository;
        $this->bbPeriodsRepository = $bbPeriodsRepository;
        $this->bbPeriodOrdersRepository = $bbPeriodOrdersRepository;
        $this->bbPeriodOrderUsersRepository = $bbPeriodOrderUsersRepository;
        $this->userCreateService = $userCreateService;
        $this->schoolCreateService = $schoolCreateService;
    }

    /**
     * 更新 Big Blue 訂單
     *
     * @param $data
     *
     * @throws SchoolCreateException
     * @throws \App\Exceptions\RepositoryException
     */
    public function update($data)
    {
        $this->initializeData($data);

        // 取得學校
        $school = $this->schoolInfoRepository->getSchoolByCodeOrAbbr($this->schoolCode, $this->schoolShortCode);

        // 沒有學校資料則建立新學校
        if (!$school) {
            $school = $this->schoolCreateService->createSchool(
                $this->schoolCode,
                $this->schoolShortCode,
                [
                    'school_name' => $this->schoolName,
                    'end_date' => null,
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

            // 建立管家
            $this->userCreateService->createUserForStaffBySchoolAbbr(
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

        // 取期數
        $period = $this->getPeriod($school->SchoolID);

        // 取訂單
        $order = $this->getOrder($period);

        // 更新
        $this->updatePeriodOrder($period, $order, $school->SchoolID);
    }

    /**
     * 產品的銷售型態是否為刪除
     *
     * @param integer $type 銷售型態 (saleType)
     *
     * @return bool
     */
    public function isProdDeleted($type)
    {
        return $type == 3;
    }

    /**
     * 授權類型是否為試用
     *
     * @param integer $type 授權類型 (periodtype)
     *
     * @return bool
     */
    public function isProductTrial($type)
    {
        return $type == 1;
    }

    /**
     * 將空間型單位轉換為 MB
     *
     * @param $quantity
     *
     * @param $unit
     *
     * @return float|int
     */
    public function convertStorageUnitToMb($quantity, $unit)
    {
        $unit = strtoupper($unit);

        switch ($unit) {
            case 'G':
                return $quantity * 1024;
            default:
                return $quantity;
        }
    }

    /**
     * @param $data
     */
    private function initializeData($data)
    {
        if (!isset($data['schoolCode']) ||
            !isset($data['schoolName']) ||
            !isset($data['schoolShortCode']) ||
            !isset($data['orderid']) ||
            !isset($data['orderAudit']) ||
            !isset($data['periodid']) ||
            !isset($data['periodtype']) ||
            !isset($data['startDate']) ||
            !isset($data['endDate'])
        ) {
            throw new \InvalidArgumentException();
        }

        $this->teamModelIds = $data['teamModelId'];
        $this->schoolCode = $data['schoolCode'];
        $this->schoolName = $data['schoolName'];
        $this->schoolShortCode = $data['schoolShortCode'];
        $this->orderId = $data['orderid'];
        $this->orderAudit = $data['orderAudit'];
        $this->periodId = $data['periodid'];
        $this->periodType = $data['periodtype'];
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];

        if (isset($data['prod']) && is_array($data['prod'])) {
            foreach ($data['prod'] as $prod) {
                if (!isset($prod['saleType']) ||
                    !isset($prod['number'])
                ) {
                    throw new \InvalidArgumentException();
                }

                // 銷售型態為刪除
                if ($this->isProdDeleted($prod['saleType'])) {
                    continue;
                }

                switch ($prod['prodcode']) {
                    // 蘇格拉底影片
                    case 'GBEI9YGR':
                        $this->socraticVideo = $prod['number'];
                        break;

                    // 蘇格拉底桌面
                    case 'A6YG9YGB':
                        $this->socraticHiEncoder = $prod['number'];
                        break;

                    // 蘇格拉底報告
                    case '6AIG9YGV':
                        $this->socraticReport = $prod['number'];
                        break;

                    // 智慧教學服務空間
                    case 'IPALYEIY':
                        $this->storage = $this->convertStorageUnitToMb($prod['number'], $prod['unit']);
                        break;

                    // 智慧學校大數據管理服務
                    case 'ON6MBDOP':
                        $this->socraticDashboard = $prod['number'];
                        break;

                    // AClassONE
                    case 'RYGVCPLY':
                        $this->aClassOne = $prod['number'];
                        break;
                    default:
                        throw new \InvalidArgumentException();
                }
            }
        }
    }

    /**
     * 取學校的期數
     *
     * @param integer $schoolId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function getPeriod($schoolId)
    {
        // 取期數
        $period = $this->bbPeriodsRepository->firstByField('period_id', $this->periodId);

        // 有期數
        if ($period) {
            // 檢查學校是否一樣，不一樣則回傳錯誤
            if ($period->SchoolID !== $schoolId) {
                throw new \InvalidArgumentException();
            }

            // 檢查類型是否一樣，不一樣則回傳錯誤
            if ($period->period_type !== $this->periodType) {
                throw new \InvalidArgumentException();
            }
        }

        return $period;
    }

    /**
     * 取訂單
     *
     * @param \App\Entities\BbPeriodsEntity $period
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function getOrder($period = null)
    {
        // 取訂單
        $order = $this->bbPeriodOrdersRepository->firstByField('order_id', $this->orderId);

        // 有訂單
        if ($order) {
            // 沒有期數
            if (!$period instanceof \App\Entities\BbPeriodsEntity) {
                throw new \InvalidArgumentException();
            }

            // 檢查期數 ID 是否一樣，不一樣則回傳錯誤
            if ($order->bb_periods_id !== $period->id) {
                throw new \InvalidArgumentException();
            }

            // 類型如果為試用，檢查 TEAM Model ID 是否一樣，不一樣則回傳錯誤
            if ($this->isProductTrial($period->period_type)) {
                // 取得該訂單的所有 TEAM Model ID
                $users = $this->bbPeriodOrderUsersRepository->findWhere(['bb_period_orders_id' => $order->id]);
                if ($users->isEmpty() || ($users->count() != count($this->teamModelIds))) {
                    throw new \InvalidArgumentException();
                }

                // 比對 TEAM Model ID，不一致則回傳錯誤
                $diff = $users->pluck('team_model_id')->diff($this->teamModelIds);
                if (!$diff->isEmpty()) {
                    throw new \InvalidArgumentException();
                }
            }
        }

        return $order;
    }

    /**
     * 更新訂單
     *
     * @param \App\Entities\BbPeriodsEntity $period
     * @param \App\Entities\BbPeriodOrdersEntity $order
     * @param integer $schoolId
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    private function updatePeriodOrder($period, $order, $schoolId)
    {
        // 有期數
        if ($period instanceof \App\Entities\BbPeriodsEntity) {
            // 更新期數
            $this->bbPeriodsRepository->update([
                'period_type' => $this->periodType,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'updated_at' => now(),
            ], $period->id);
        } else {
            // 新增期數
            $period = $this->bbPeriodsRepository->create([
                'period_id' => $this->periodId,
                'period_type' => $this->periodType,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'SchoolID' => $schoolId,
            ]);
        }

        // 有訂單
        if ($order instanceof \App\Entities\BbPeriodOrdersEntity) {
            // 更新訂單
            $this->bbPeriodOrdersRepository->update([
                'order_audit' => $this->orderAudit,
                'storage' => $this->storage,
                'socratic_video' => $this->socraticVideo,
                'socratic_hi_encoder' => $this->socraticHiEncoder,
                'socratic_report' => $this->socraticReport,
                'socratic_dashboard' => $this->socraticDashboard,
                'aclass_one' => $this->aClassOne,
                'updated_at' => now(),
            ], $order->id);
        } else {
            // 新增訂單
            $order = $this->bbPeriodOrdersRepository->create([
                'order_id' => $this->orderId,
                'order_audit' => $this->orderAudit,
                'storage' => $this->storage,
                'socratic_video' => $this->socraticVideo,
                'socratic_hi_encoder' => $this->socraticHiEncoder,
                'socratic_report' => $this->socraticReport,
                'socratic_dashboard' => $this->socraticDashboard,
                'aclass_one' => $this->aClassOne,
                'bb_periods_id' => $period->id,
            ]);

            // 試用授權
            if ($this->isProductTrial($period->period_type) && $this->teamModelIds) {
                $createTeamModelIds = null;
                foreach ($this->teamModelIds as $teamModelId) {
                    $createTeamModelIds[]['team_model_id'] = $teamModelId;
                }

                if ($createTeamModelIds) {
                    $order->hasManyBbPeriodOrderUsers()->createMany($createTeamModelIds);
                }
            }
        }

        return true;
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
}