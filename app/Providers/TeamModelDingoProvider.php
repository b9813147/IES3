<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Dingo\Api\Routing\Route;
use Dingo\Api\Contract\Auth\Provider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\ExceptionCodes\OAuthExceptionCode;
use App\Exceptions\UnauthorizedSchoolException;
use App\Supports\AuthorizationSupport;

/**
 * Team Model Token 驗證
 *
 * @package App\Providers
 */
class TeamModelDingoProvider implements Provider
{
    use AuthorizationSupport;

    /**
     * Illuminate Factory.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * The guard driver name.
     *
     * @var string
     */
    protected $guard = 'team_model';

    /**
     * PassportAuthenticationProvider constructor.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth->guard($this->guard);
    }

    /**
     * Authenticate request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        if (!$user = $this->auth->user()) {
            throw new UnauthorizedHttpException(null, null, null, OAuthExceptionCode::INVALID_ACCESS_TOKEN);
        }

        // 檢查學校授權到期日
        if ($this->isSchoolValid($user->IDLevel, $user->CreateDate, $user->EndDate) !== true) {
            throw new UnauthorizedSchoolException(null, null, null, OAuthExceptionCode::SCHOOL_AUTHORIZATION_IS_EXPIRED);
        }

        return $user;
    }
}