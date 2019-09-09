<?php

namespace App\ExceptionCodes;

/**
 * 蘇格拉底相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
abstract class SokratesExceptionCode
{
    /** 蘇格拉底加入頻道失敗 */
    const SOKRATES_ADD_CHANNEL_ERROR = 160000;

    /** 蘇格拉底連線失敗 */
    const SOKRATES_SEND_ERROR = 160001;
}