<?php

namespace App\Services\TeamModel;

use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use App\Repositories\Eloquent\Oauth2MemberRepository;
use App\Constants\Models\OAuth2MemberConstant;

/**
 * Team Model Token Guard，專門驗證 Team Model Server 發出的 Token
 *
 * @package App\Services\TeamModel
 */
class TeamModelTokenGuard
{
    /**
     * The resource server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * The user provider implementation.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * The token repository instance.
     *
     * @var \App\Repositories\Eloquent\Oauth2MemberRepository
     */
    protected $tokens;

    /**
     * Create a new token guard instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer $server
     * @param  \Illuminate\Contracts\Auth\UserProvider $provider
     * @param  \App\Repositories\Eloquent\Oauth2MemberRepository $tokens
     *
     * @return void
     */
    public function __construct(ResourceServer $server,
                                UserProvider $provider,
                                Oauth2MemberRepository $tokens)
    {
        $this->server = $server;
        $this->provider = $provider;
        $this->tokens = $tokens;
    }

    /**
     * Get the user for the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function user(Request $request)
    {
        if ($request->bearerToken()) {
            return $this->authenticateViaBearerToken($request);
        }
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    protected function authenticateViaBearerToken($request)
    {
        $psr = (new DiactorosFactory)->createRequest($request);

        try {
            // 解析 Team Model Token
            $psr = $this->server->validateAuthenticatedRequest($psr);

            // 驗證 Team Model ID 是否有綁定 IES 帳號
            $token = $this->tokens->findForUser(OAuth2MemberConstant::SSO_SERVER_TEAM_MODE, $psr->getAttribute('oauth_team_model_id'));

            if (!$token) {
                return;
            }

            // 取得使用者資料
            $user = $this->provider->retrieveById(
                $token->MemberID
            );

            return $user ? $user->withAccessToken($token) : null;
        } catch (OAuthServerException $e) {
            return Container::getInstance()->make(
                ExceptionHandler::class
            )->report($e);
        }
    }
}