<?php

namespace App\ExceptionCodes;

/**
 * 學校相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
abstract class SchoolExceptionCode
{
    /** 學校建立錯誤 */
    const SCHOOL_CREATE_ERROR = 150000;

    /** 找不到學校 */
    const SCHOOL_NOT_FOUND = 150001;
}