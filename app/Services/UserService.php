<?php

namespace App\Services;

use App\Repositories\Eloquent\MemberRepository;

/**
 * 查詢使用者相關 Service
 *
 * @package App\Services
 */
class UserService
{
    /** @var MemberRepository */
    protected $repository;

    /**
     * UserService constructor.
     *
     * @param MemberRepository $repository
     */
    public function __construct(MemberRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 查詢使用者資料
     *
     * @param $memberID
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getUserInfo($memberID)
    {
        return $this->repository->findForUser($memberID);
    }
}