<?php

namespace App\Services;

use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\SystemAuthorityRepository;
use App\Supports\TimeSupport;

/**
 * 建立使用者相關 Service
 *
 * @package App\Services
 */
class UserCreateService
{
    use TimeSupport;

    /** @var MemberRepository */
    protected $memberRepository;

    /** @var SystemAuthorityRepository */
    protected $systemAuthorityRepository;

    /**
     * UserService constructor.
     *
     * @param MemberRepository $memberRepository
     * @param SystemAuthorityRepository $systemAuthorityRepository
     */
    public function __construct(
        MemberRepository $memberRepository,
        SystemAuthorityRepository $systemAuthorityRepository
    )
    {
        $this->memberRepository = $memberRepository;
        $this->systemAuthorityRepository = $systemAuthorityRepository;
    }

    /**
     * 建立老師帳號
     *
     * @param array $userData
     * @param array $userAuthority
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createUserForTeacher($userData, $userAuthority = [])
    {
        // 使用者預設基本資料
        $defaultUserData = [
            'login_id' => null,
            'password' => ies_login_id_random(),
            'real_name' => 'User',
            'gender' => 'M',
            'email' => '',
            'school_id' => 0,
            'status' => 1,
            'activation_code_id' => null
        ];

        $userData = array_merge($defaultUserData, $userData);

        // 沒有帳號則建立隨機帳號
        if (empty($userData['login_id'])) {
            do {
                $loginId = ies_login_id_random();
            } while ($this->memberRepository->findWhere(['LoginID' => $loginId])->isNotEmpty());
            $userData['login_id'] = $loginId;
        }

        // 使用者權限
        $defaultUserAuthority = [
            'id_level' => 'T',
            's_date' => $this->currentDateString(),
            'e_date' => $this->currentAddDayToDateString(30),
            'analysis' => 0,
            'ezcms_size' => 0,
            'system_manager' => 0,
            'authorization_type' => 0,
        ];

        $userAuthority = array_merge($defaultUserAuthority, $userAuthority);

        return $this->createUser($userData, $userAuthority);
    }

    /**
     * 使用學校簡碼創建學校管理員帳號
     *
     * @param string $schoolAbbr 學校簡碼
     * @param array $userData
     * @param array $userAuthority
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createUserForRootBySchoolAbbr($schoolAbbr, $userData, $userAuthority = [])
    {
        // 使用者預設基本資料
        $defaultUserData = [
            'login_id' => null,
            'password' => ies_login_id_random(),
            'real_name' => 'User',
            'gender' => 'M',
            'email' => '',
            'school_id' => 0,
            'status' => 1,
            'activation_code_id' => null
        ];

        $schoolAbbr = strtolower($schoolAbbr);

        // 格式：root_簡碼
        $userData['login_id'] = 'root_' . $schoolAbbr;

        // 格式：habook-簡碼
        $userData['password'] = 'habook-' . $schoolAbbr;

        $userData = array_merge($defaultUserData, $userData);

        // 沒有帳號則建立隨機帳號
        if (empty($userData['login_id'])) {
            do {
                $loginId = ies_login_id_random();
            } while ($this->memberRepository->findWhere(['LoginID' => $loginId])->isNotEmpty());
            $userData['login_id'] = $loginId;
        }

        // 使用者權限
        $defaultUserAuthority = [
            'id_level' => 'D',
            's_date' => $this->currentDateString(),
            'e_date' => $this->currentAddDayToDateString(30),
            'analysis' => 0,
            'ezcms_size' => 0,
            'system_manager' => 1,
            'authorization_type' => 2,
        ];

        $userAuthority = array_merge($defaultUserAuthority, $userAuthority);

        return $this->createUser($userData, $userAuthority);
    }

    /**
     * 使用學校簡碼創建學校行政人員帳號
     *
     * @param string $schoolAbbr 學校簡碼
     * @param array $userData
     * @param array $userAuthority
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createUserForStaffBySchoolAbbr($schoolAbbr, $userData, $userAuthority = [])
    {
        // 使用者預設基本資料
        $defaultUserData = [
            'login_id' => null,
            'password' => ies_login_id_random(),
            'real_name' => 'User',
            'gender' => 'M',
            'email' => '',
            'school_id' => 0,
            'status' => 1,
            'activation_code_id' => null
        ];

        $schoolAbbr = strtolower($schoolAbbr);

        // 格式：m_簡碼
        $userData['login_id'] = 'm_' . $schoolAbbr;

        // 格式：m_簡碼
        $userData['password'] = 'm_' . $schoolAbbr;

        $userData = array_merge($defaultUserData, $userData);

        // 沒有帳號則建立隨機帳號
        if (empty($userData['login_id'])) {
            do {
                $loginId = ies_login_id_random();
            } while ($this->memberRepository->findWhere(['LoginID' => $loginId])->isNotEmpty());
            $userData['login_id'] = $loginId;
        }

        // 使用者權限
        $defaultUserAuthority = [
            'id_level' => 'D',
            's_date' => $this->currentDateString(),
            'e_date' => $this->currentAddDayToDateString(30),
            'analysis' => 0,
            'ezcms_size' => 0,
            'system_manager' => 0,
            'authorization_type' => 2,
        ];

        $userAuthority = array_merge($defaultUserAuthority, $userAuthority);

        return $this->createUser($userData, $userAuthority);
    }

    /**
     * 建立使用者帳號
     *
     * @param array $userData
     * @param array $userAuthority
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    protected function createUser($userData, $userAuthority)
    {
        // 建立使用者帳號
        $this->memberRepository->create([
            'LoginID' => $userData['login_id'],
            'Password' => ies_password_hash($userData['password']),
            'RealName' => $userData['real_name'],
            'Gender' => $userData['gender'],
            'Email' => $userData['email'],
            'SchoolID' => $userData['school_id'],
            'RegisterTime' => $this->currentTimeString(),
            'status' => $userData['status'],
            'activation_code_id' => $userData['activation_code_id']
        ]);

        $user = $this->memberRepository->findByField('LoginID', $userData['login_id'])->first();

        // 建立使用者權限
        $this->systemAuthorityRepository->create([
            'MemberID' => $user->MemberID,
            'IDLevel' => $userAuthority['id_level'],
            'SDate' => $userAuthority['s_date'],
            'EDate' => $userAuthority['e_date'],
            'analysis' => $userAuthority['analysis'],
            'EzcmsSize' => $userAuthority['ezcms_size'],
            'SystemManager' => $userAuthority['system_manager'],
            'authorization_type' => $userAuthority['authorization_type'],
        ]);

        return $this->memberRepository->find($user->MemberID);
    }
}
