<?php

namespace App\Supports;

use Carbon\Carbon;

trait TimeSupport
{
    /**
     * 取得當前系統時間並格式化為 'Y-m-d H:i:s'
     *
     * @return string
     */
    protected function currentTimeString()
    {
        return Carbon::now()->toDateTimeString();
    }

    /**
     * 將時間戳轉換為 'Y-m-d H:i:s'
     *
     * @param integer $timestamp
     *
     * @return null|string
     */
    protected function convertTimestampToDateTimeString($timestamp)
    {
        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp)->toDateTimeString();
        }

        return null;
    }

    /**
     * 當前系統時間增加指定天數並格式化為 'Y-m-d'
     *
     * @param integer $day
     *
     * @return string
     */
    protected function currentAddDayToDateString($day)
    {
        return Carbon::now()->addDay($day)->toDateString();
    }

    /**
     * 取得當前系統時間並格式化為 'Y-m-d'
     *
     * @return string
     */
    protected function currentDateString()
    {
        return Carbon::now()->toDateString();
    }
}