<?php

namespace App\Http\Controllers\Api\V1\TeamModel;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Requests\Api\V1\TeamModel\DestroyUserRequest;
use App\Services\TeamModel\TeamModelIDService;
use App\ExceptionCodes\EventExceptionCode;
use Dingo\Api\Exception\ResourceException;

/**
 * TEAM Model ID 綁定的 IES 個人基本資料
 *
 * @package App\Http\Controllers\Api\V1\Users
 */
class UserController extends BaseApiV1Controller
{
    /**
     * @var TeamModelIDService
     */
    protected $teamModelService;

    /**
     * UserController constructor.
     *
     * @param TeamModelIDService $teamModelService
     */
    public function __construct(TeamModelIDService $teamModelService)
    {
        $this->teamModelService = $teamModelService;
    }

    /**
     * TEAM Model ID 解除綁定 IES 帳號
     *
     * @param DestroyUserRequest $request
     *
     * @return \Dingo\Api\Http\Response|void
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function destroy(DestroyUserRequest $request)
    {
        $teamModelId = $request->input('id');

        // 取 TEAM Model ID 綁定的資料
        $user = $this->teamModelService->getUserByTeamModelId($teamModelId);
        if (!$user) {
            throw new ResourceException('', null, null, [], EventExceptionCode::EVENT_TEAM_MODEL_ID_NOT_EXISTS);
        }

        try {
            // 解除綁定
            $this->teamModelService->unbinding($user->MemberID, $teamModelId);
        } catch (\Exception $e) {
            return $this->response->errorInternal();
        }

        return $this->response->noContent();
    }
}