<?php
namespace App\Supports;

use Carbon\Carbon;

trait AuthorizationSupport
{
    /**
     * 帳號所屬的學校是否在有效的授權時間內，目前不判斷啟用日
     *
     * @param string $level 使用者身份
     * @param string $startDate 授權啟用日期
     * @param string $endDate 授權到期日期
     *
     * @return bool
     */
    protected function isSchoolValid($level, $startDate, $endDate)
    {
        if ($level == 'T') {
            if (empty($endDate)) {
                return true;
            }

            try {
                return Carbon::today()->lessThanOrEqualTo(Carbon::parse($endDate)->endOfDay());
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * 帳號所屬的學校授權碼是否在有效的授權時間內，目前不判斷啟用日
     *
     * @param string $startDate 授權啟用日期
     * @param string $endDate 授權到期日期
     *
     * @return bool
     */
    protected function isSchoolActivationCodeValid($startDate, $endDate)
    {
        if (empty($endDate)) {
            return true;
        }

        try {
            return Carbon::today()->lessThanOrEqualTo(Carbon::parse($endDate)->endOfDay());
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * 帳號所屬的學校授權碼是否已超過授權人數上限
     *
     * @param integer|null $maxMember 啟用碼授權上限人數
     * @param integer $currentMember 當前使用者人數
     *
     * @return bool
     */
    protected function isSchoolMaxMemberValid($maxMember, $currentMember)
    {
        if (is_null($maxMember)) {
            return true;
        }

        if (is_int($currentMember) && ($currentMember < $maxMember)) {
            return true;
        }

        return false;
    }
}