<?php

namespace App\Constants\Models;

/**
 * 定義 Table `semesterinfo` 的欄位參數
 *
 * @package App\Constants
 */
abstract class SemesterInfoConstant
{
    /** sorder = 0 為上學期 */
    const S_ORDER_FIRST = 0;

    /** sorder = 1 為下學期 */
    const S_ORDER_SECOND = 1;
}