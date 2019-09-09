<?php

namespace App\Http\Controllers\Api\V1\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\UserService;
use App\ExceptionCodes\UserExceptionCode;
use Dingo\Api\Exception\ResourceException;

/**
 * 個人基本資料
 *
 * @package App\Http\Controllers\Api\V1\Users
 */
class UserController extends BaseApiV1Controller
{
    /** @var UserService */
    protected $service;

    /**
     * UserController constructor.
     *
     * @param UserService $service
     */
    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * 查詢個人基本資料
     *
     * @return UserResource
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function index()
    {
        $user = $this->auth->user();
        $userInfo = $this->service->getUserInfo($user->MemberID);
        if (!$user) {
            throw new ResourceException('取得使用者基本資料失敗', null, null, [], UserExceptionCode::GET_USER_INFO_FAILED);
        }
        $userInfo->photo_url = null;

        return new UserResource($userInfo);
    }
}