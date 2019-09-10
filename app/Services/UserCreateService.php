<?php

namespace App\Services;

use App\Repositories\MemberRepository;
use App\Repositories\SystemAuthorityRepository;
use App\Supports\TimeSupport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as ModelAlias;

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
        $this->memberRepository          = $memberRepository;
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
     */
    public function createUserForTeacher($userData, $userAuthority = [])
    {

        // 使用者預設基本資料
        $defaultUserData = [
//            'LoginID' => null,
            'Password'           => ies_login_id_random(),
            'RealName'           => 'User',
            'Gender'             => 'M',
            'Email'              => '',
            'SchoolID'           => 0,
            'Status'             => 1,
            'activation_code_id' => null
        ];

        $userData = array_merge($defaultUserData, $userData);

        // 檢查TeamModelID 使否 存在 並啟用
        if ($this->memberRepository->findWhere(['LoginID' => $userData['LoginID'], 'Status' => 1])->isNotEmpty()) {
            return false;
        }

        if ($this->memberRepository->findWhere(['LoginID' => $userData['LoginID'], 'Status' => 0])->isNotEmpty()) {
            return $this->updateUser($userData);
        }

        // 沒有帳號則建立隨機帳號
//        if (empt$userData->['LoginID'])) {
//            do {
//                $loginId = ies_login_id_random();
//            } while ($this->memberRepository->findWhere(['LoginID' => $loginId])->isNotEmpty());
//          $userData->['LoginID'] = $loginId;
//        }
        // 使用者權限
        $defaultUserAuthority = [
            'IDLevel'            => 'T',
            'SDate'              => $this->currentDateString(),
            'EDate'              => $this->currentAddDayToDateString(30),
            'analysis'           => 0,
            'EzcmsSize'          => 0,
            'SystemManager'      => 0,
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
            'login_id'           => null,
            'password'           => ies_login_id_random(),
            'real_name'          => 'User',
            'gender'             => 'M',
            'email'              => '',
            'school_id'          => 0,
            'status'             => 1,
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
            'id_level'           => 'D',
            's_date'             => $this->currentDateString(),
            'e_date'             => $this->currentAddDayToDateString(30),
            'analysis'           => 0,
            'ezcms_size'         => 0,
            'system_manager'     => 1,
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
            'login_id'           => null,
            'password'           => ies_login_id_random(),
            'real_name'          => 'User',
            'gender'             => 'M',
            'email'              => '',
            'school_id'          => 0,
            'status'             => 1,
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
            'id_level'           => 'D',
            's_date'             => $this->currentDateString(),
            'e_date'             => $this->currentAddDayToDateString(30),
            'analysis'           => 0,
            'ezcms_size'         => 0,
            'system_manager'     => 0,
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
     */
    protected function createUser($userData, $userAuthority)
    {
        // 建立使用者帳號
        $this->memberRepository->create([
            'LoginID'            => $userData['LoginID'],
            'Password'           => ies_password_hash($userData['Password']),
            'RealName'           => $userData['RealName'],
            'Gender'             => $userData['Gender'],
            'Email'              => $userData['Email'],
            'SchoolID'           => $userData['SchoolID'],
            'RegisterTime'       => $this->currentTimeString(),
            'Status'             => $userData['Status'],
            'activation_code_id' => $userData['activation_code_id']
        ]);

        $user = $this->memberRepository->firstBy('LoginID', $userData['LoginID']);

        // 建立使用者權限
        $this->systemAuthorityRepository->create([
            'MemberID'           => $user->MemberID,
            'IDLevel'            => $userAuthority['IDLevel'],
            'SDate'              => $userAuthority['SDate'],
            'EDate'              => $userAuthority['EDate'],
            'analysis'           => $userAuthority['analysis'],
            'EzcmsSize'          => $userAuthority['EzcmsSize'],
            'SystemManager'      => $userAuthority['SystemManager'],
            'authorization_type' => $userAuthority['authorization_type'],
        ]);

        return $this->memberRepository->find($user->MemberID);
    }

    /**
     * 更新使用者帳號
     *
     * @param array $userData
     * @return Collection|ModelAlias
     */
    protected function updateUser($userData)
    {
        $this->memberRepository->updateBy('LoginID', $userData['LoginID'], $userData);
        $user = $this->memberRepository->firstBy('LoginID', $userData['LoginID']);

        return $this->memberRepository->find($user->MemberID);
    }
}
