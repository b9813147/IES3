<?php

namespace App\Constants\Models;

/**
 * 定義 Table `systemauthority` 的欄位參數
 *
 * @package App\Constants
 */
abstract class SystemAuthorityConstant
{
    /** analysis = 0 為沒有蘇格拉底授權 */
    const ANALYSIS_NO_AUTHORIZATION = 0;

    /** analysis = 1 為蘇格拉底報告授權 */
    const ANALYSIS_REPORT = 1;

    /** analysis = 2 為蘇格拉底桌面授權 */
    const ANALYSIS_HI_ENCODER = 2;

    /** analysis = 3 為蘇格拉底影片授權 */
    const ANALYSIS_VIDEO = 3;
}