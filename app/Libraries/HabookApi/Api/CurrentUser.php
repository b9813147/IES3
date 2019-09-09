<?php

namespace App\Libraries\HabookApi\Api;

class CurrentUser extends AbstractApi
{
    /**
     * 取使用者資料
     *
     * @param string $ticket 使用者的 ticket
     *
     * @return array
     *
     * @throws
     */
    public function show($ticket)
    {
        $parameters = [
            'idToken' => $ticket,
            'method' => 'get',
            'option' => 'userInfo',
            'extraInfo' => new \stdClass()
        ];

        return $this->postRpc('account', 'UserInfoManage', $parameters);
    }
}