<?php

namespace App\Services;

use App\Repositories\Eloquent\SchoolInfoRepository;
use App\Repositories\Eloquent\SchoolActivationCodesRepository;
use App\Repositories\Eloquent\MemberRepository;
use App\Supports\AuthorizationSupport;
use App\Exceptions\SchoolNotFoundException;
use App\Exceptions\SchoolExpiredException;
use App\Exceptions\SchoolActivationCodeExpiredException;
use App\Exceptions\SchoolActivationCodeMemberLimitExceededException;

/**
 * 查詢學校相關 Service
 *
 * @package App\Services
 */
class SchoolService
{
    use AuthorizationSupport;

    /**
     * @var SchoolInfoRepository
     */
    protected $schoolInfoRepository;

    /**
     * @var SchoolInfoRepository
     */
    protected $schoolActivationCodesRepository;

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * SchoolService constructor.
     *
     * @param SchoolInfoRepository $schoolInfoRepository
     * @param SchoolActivationCodesRepository $schoolActivationCodesRepository
     * @param MemberRepository $memberRepository
     */
    public function __construct(
        SchoolInfoRepository $schoolInfoRepository,
        SchoolActivationCodesRepository $schoolActivationCodesRepository,
        MemberRepository $memberRepository
    )
    {
        $this->schoolInfoRepository = $schoolInfoRepository;
        $this->schoolActivationCodesRepository = $schoolActivationCodesRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * 使用啟用碼查詢學校資料
     *
     * @param $code
     *
     * @return mixed
     *
     * @throws SchoolActivationCodeExpiredException
     * @throws SchoolExpiredException
     * @throws SchoolNotFoundException
     * @throws SchoolActivationCodeMemberLimitExceededException
     * @throws \App\Exceptions\RepositoryException
     */
    public function getSchoolByActivationCode($code)
    {
        $schoolInfo = $this->schoolActivationCodesRepository->findForSchool($code);
        if (empty($schoolInfo)) {
            throw new SchoolNotFoundException();
        }

        // 檢查學校授權到期日
        if ($this->isSchoolValid('T', $schoolInfo->CreateDate, $schoolInfo->EndDate) !== true) {
            throw new SchoolExpiredException();
        }

        // 檢查啟用碼到期日期
        if ($this->isSchoolActivationCodeValid('', $schoolInfo->end_date) !== true) {
            throw new SchoolActivationCodeExpiredException();
        }

        // 檢查啟用碼授權人數
        if (is_int($schoolInfo->max_member)) {
            // 取得目前已使用啟用碼的授權人數
            $countMember = $this->memberRepository->countUserByActivationCodeId($schoolInfo->id);
            if ($this->isSchoolMaxMemberValid($schoolInfo->max_member, $countMember) !== true) {
                throw new SchoolActivationCodeMemberLimitExceededException();
            }
        }

        return $schoolInfo;
    }
}