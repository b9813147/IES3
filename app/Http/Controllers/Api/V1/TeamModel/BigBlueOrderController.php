<?php

namespace App\Http\Controllers\Api\V1\TeamModel;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Resources\Api\V1\BigBlueOrderResource;
use App\Services\BigBlueOrderService;
use App\Services\BigBlueOrderAuthorizationService;
use App\Http\Requests\Api\V1\TeamModel\UpdateBigBlueOrderRequest;
use App\Constants\Models\BbPeriodsConstant;
use App\ExceptionCodes\EventExceptionCode;
use App\Exceptions\SchoolNotFoundException;
use App\ExceptionCodes\SchoolExceptionCode;
use App\Exceptions\SchoolCreateException;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BigBlueOrderController extends BaseApiV1Controller
{
    /**
     * @var BigBlueOrderService
     */
    protected $bigBlueOrderService;

    /**
     * @var BigBlueOrderAuthorizationService
     */
    protected $bigBlueOrderAuthorizationService;

    /**
     * BigBlueOrderController constructor.
     *
     * @param BigBlueOrderService $bigBlueOrderService
     * @param BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService
     */
    public function __construct(BigBlueOrderService $bigBlueOrderService, BigBlueOrderAuthorizationService $bigBlueOrderAuthorizationService)
    {
        $this->bigBlueOrderService = $bigBlueOrderService;
        $this->bigBlueOrderAuthorizationService = $bigBlueOrderAuthorizationService;
    }

    /**
     * 更新或新增 Big Blue 訂單並更新授權
     *
     * @param UpdateBigBlueOrderRequest $request
     *
     * @return BigBlueOrderResource
     */
    public function update(UpdateBigBlueOrderRequest $request)
    {
        try {
            // 更新訂單
            $this->bigBlueOrderService->update($request->input('msg'));

            // 更新授權
            switch ($request->input('msg.periodtype')) {
                case BbPeriodsConstant::PERIOD_TYPE_SALES:
                    // 更新銷售授權
                    $this->bigBlueOrderAuthorizationService->updateSalesBySchoolCode($request->input('msg.schoolCode'));
                    break;
                case BbPeriodsConstant::PERIOD_TYPE_TRIAL:
                    // 更新試用授權
                    $this->bigBlueOrderAuthorizationService->updateTrialAuthorizationByTeamModelIds($request->input('msg.teamModelId'));
                    break;
            }

            return new BigBlueOrderResource([]);
        } catch (\InvalidArgumentException $e) {
            throw new ResourceException();
        } catch (SchoolCreateException $e) {
            throw new ResourceException('', null, null, [], SchoolExceptionCode::SCHOOL_CREATE_ERROR);
        } catch (SchoolNotFoundException $e) {
            throw new ResourceException('', null, null, [], SchoolExceptionCode::SCHOOL_NOT_FOUND);
        } catch (\Exception $e) {
            throw new HttpException(500, '', null, []);
        }
    }
}