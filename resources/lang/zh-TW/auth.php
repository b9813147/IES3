<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => '使用者名稱或密碼錯誤。',
    'throttle' => '嘗試登入太多次，請在 :seconds 秒後再試。',
    'system_failed' => '系統錯誤',
    'team_model_id' => [
        'binding_already' => 'TEAM Model ID :team_model_id 已綁定',
        'not_found' => '找不到 TEAM Model ID，請重新從醍摩豆網頁登入',
    ],
    'school' => [
        'activation_code' => [
            'not_found' => '找不到啟用碼的資料',
            'expired' => '啟用碼已到期',
            'member_limit_exceeded' => '啟用碼使用人數已達上限',
        ],
        'authorization' => [
            'expired' => '學校授權已到期',
        ],
    ],

];
