<?php

use App\Supports\Ies2StorageSupport;

if (!function_exists('ies_password_hash')) {
    /**
     * Creates a password hash for IES.
     *
     * @param  string $password
     * @return string
     */
    function ies_password_hash($password)
    {
        return sha1($password);
    }
}

if (!function_exists('ies_login_id_random')) {
    /**
     * Creates a random login id for IES.
     *
     * @return string
     */
    function ies_login_id_random()
    {
        return md5(uniqid(mt_rand(), true));
    }
}

if (!function_exists('habook_base64_encode')) {
    /**
     * IES 2 base64 encode
     *
     * @param string $data
     *
     * @return string
     */
    function habook_base64_encode($data)
    {
        return strtr(base64_encode($data), '+/', '-_');
    }
}

if (!function_exists('habook_base64_encode')) {
    /**
     * IES 2 base64 decode
     *
     * @param string $data
     *
     * @return bool|string
     */
    function habook_base64_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('avatar_path')) {
    /**
     * 使用者頭像的檔案路徑，不包含檔案
     *
     * @param integer $memberId
     *
     * @return string
     */
    function avatar_path($memberId)
    {
        return Ies2StorageSupport::avatar_path($memberId);
    }
}

if (!function_exists('avatar_url')) {
    /**
     * 使用者頭像的 URL
     *
     * @param integer $memberId Member ID
     * @param string $fileName 頭像的檔案名稱
     * @param bool $toEncode 檔案是否需要 habook_base64_encode 編碼
     *
     * @return null|string
     */
    function avatar_url($memberId, $fileName, $toEncode = true)
    {
        return Ies2StorageSupport::avatar_url($memberId, $fileName, $toEncode);
    }
}