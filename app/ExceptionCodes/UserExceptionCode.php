<?php

namespace App\ExceptionCodes;

/**
 * 使用者相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
abstract class UserExceptionCode
{
    /** 取得使用者基本資料失敗 */
    const GET_USER_INFO_FAILED = 120000;
}