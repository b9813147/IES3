<?php

namespace App\ExceptionCodes;

/**
 * 活動相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
abstract class EventExceptionCode
{
    /** TEAM Model ID 在 IES 上不存在 */
    const EVENT_TEAM_MODEL_ID_NOT_EXISTS = 140000;

    /** 活動還沒開始 */
    const EVENT_NOT_START = 140001;

    /** 活動已經結束 */
    const EVENT_IS_OVER = 140002;

    /** 沒有活動 */
    const EVENT_NOT_FOUND = 140003;

    /** 取活動錯誤 */
    const EVENT_GET_ERROR = 140004;

    /** 活動加入使用者錯誤 */
    const EVENT_ADD_USER_ERROR = 140005;
}