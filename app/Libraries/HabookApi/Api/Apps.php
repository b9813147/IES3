<?php

namespace App\Libraries\HabookApi\Api;

class Apps extends AbstractApi
{
    /**
     * 取得操作服務的 API Token
     *
     * @param string $clientId            應用程式識別碼
     * @param string $verificationCode    驗證碼
     * @param string $verificationCodeVer 驗證碼版本號
     *
     * @return array
     *
     * @throws
     */
    public function createRegisterToken($clientId, $verificationCode, $verificationCodeVer)
    {
        $parameters = [
            'clientId' => $clientId,
            'verificationCode' => $verificationCode,
            'verificationCodeVer' => $verificationCodeVer,
            'extraInfo' => new \stdClass()
        ];

        return $this->postRpc('service', 'Regist', $parameters);
    }
}