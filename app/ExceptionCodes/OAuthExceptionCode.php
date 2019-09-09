<?php

namespace App\ExceptionCodes;

/**
 * OAuth 相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
class OAuthExceptionCode
{
    const UNSUPPORTED_GRANT_TYPE = 100000;
    const INVALID_REQUEST = 100001;
    const INVALID_CLIENT = 100002;
    const INVALID_SCOPE = 100003;
    const INVALID_CREDENTIALS = 100004;
    const SERVER_ERROR = 100005;
    const INVALID_REFRESH_TOKEN = 100006;
    const ACCESS_DENIED = 100007;
    const INVALID_GRANT = 100008;
    const INVALID_ACCESS_TOKEN = 100009;
    const INVALID_VERSION = 100010;

    /** Member ID 已經綁定 */
    const MEMBER_ID_ALREADY_BINDING = 100011;

    /** Team Model ID 已經綁定 */
    const TEAM_MODEL_ID_ALREADY_BINDING = 100012;

    /** 學校授權到期 */
    const SCHOOL_AUTHORIZATION_IS_EXPIRED = 100013;
}