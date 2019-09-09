<?php

namespace App\Supports;

use Illuminate\Support\Facades\Storage;

class Ies2StorageSupport
{
    /**
     * 使用者頭像的檔案路徑，不包含檔案
     *
     * @param integer $memberId Member ID
     *
     * @return string
     */
    public static function avatar_path($memberId)
    {
        return 'Field/' . $memberId . '/Head_Img';
    }

    /**
     * 使用者頭像的 URL
     *
     * @param $memberId Member ID
     * @param $fileName 頭像的檔案名稱
     * @param bool $toEncode 檔案是否需要 habook_base64_encode 編碼
     *
     * @return null|string
     */
    public static function avatar_url($memberId, $fileName, $toEncode = true)
    {
        if (empty($memberId) || empty($fileName)) {
            return null;
        }

        if ($toEncode) {
            $fileName = habook_base64_encode($fileName);
        }

        return Storage::disk('ies2')->url('Field/' . $memberId . '/Head_Img/' . $fileName);
    }
}