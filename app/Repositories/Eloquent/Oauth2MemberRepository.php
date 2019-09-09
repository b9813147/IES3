<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class Oauth2MemberRepository
 *
 * @package App\Repositories\Eloquent
 */
class Oauth2MemberRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\Oauth2MemberEntity";
    }

    public function boot()
    {

    }

    /**
     * 查詢使用者是否已綁定第三方帳號
     *
     * @param string  $sso_server
     * @param integer $memberID
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function isBindingByMemberID($sso_server, $memberID)
    {
        return !$this->findWhere(['sso_server' => $sso_server, 'MemberID' => $memberID])->isEmpty();
    }

    /**
     * 查詢第三方帳號是否已綁定
     *
     * @param string $sso_server
     * @param string $account
     *
     * @return bool
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function isBindingByOauth2Account($sso_server, $account)
    {
        return !$this->findWhere(['sso_server' => $sso_server, 'oauth2_account' => $account])->isEmpty();
    }

    /**
     * 查詢第三方帳號綁定的 Member
     *
     * @param string $sso_server
     * @param string $account
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForUser($sso_server, $account)
    {
        return $this->findWhere(['sso_server' => $sso_server, 'oauth2_account' => $account])->first();
    }
}