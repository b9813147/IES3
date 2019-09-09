<?php

namespace App\Constants\Models;

/**
 * 定義 Table `bb_periods` 的欄位參數
 *
 * @package App\Constants
 */
abstract class BbPeriodsConstant
{
    /** period_type = 0 為銷售授權 */
    const PERIOD_TYPE_SALES = 0;

    /** period_type = 1 為試用授權 */
    const PERIOD_TYPE_TRIAL = 1;
}