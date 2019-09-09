<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 醍摩豆大賽
    |--------------------------------------------------------------------------
    | 2018.school_id 預設指定的學校 ID
    | 2018.event_id 指定的活動 ID
    | 2018.oauth_client_id 指定可使用 API 的 OAuth Client ID
    |
    | 2019.event_id 指定的活動 ID
    | 2019.oauth_client_id 指定可使用 API 的 OAuth Client ID
    |
    */
    'team_model_contest' => [
        '2018' => [
            'school_id' => null,
            'event_id' => null,
            'oauth_client_id' => null,
        ],
        '2019' => [
            'event_id' => 2,
            'oauth_client_id' => [4],
        ],
    ],

];