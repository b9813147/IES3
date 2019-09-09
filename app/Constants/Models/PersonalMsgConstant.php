<?php

namespace App\Constants\Models;

/**
 * 定義 Table `personalmsg` 的欄位參數
 *
 * @package App\Constants
 */
abstract class PersonalMsgConstant
{
    /** GetStatus = N 未讀 */
    const GET_STATUS_UNREAD = 'N';

    /** SendStatus = N 未刪除 */
    const SEND_STATUS_NOT_DELETED = 'N';
}