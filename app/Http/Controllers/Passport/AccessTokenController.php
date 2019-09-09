<?php

namespace App\Http\Controllers\Passport;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;
use Lcobucci\JWT\Parser;
use App\Services\TeamModel\TeamModelIDService;
use App\ExceptionCodes\CommonExceptionCode;
use App\ExceptionCodes\OAuthExceptionCode;
use Illuminate\Database\QueryException;

/**
 * Class AccessTokenController
 *
 * @package App\Http\Controllers\Passport
 */
class AccessTokenController extends PassportAccessTokenController
{
    use HandlesOAuthErrors;

    /** @var TeamModelIDService */
    protected $teamModelIDService;

    /**
     * AccessTokenController constructor.
     *
     * @param AuthorizationServer $server
     * @param TokenRepository $tokens
     * @param Parser $jwt
     * @param TeamModelIDService $teamModelIDService
     */
    public function __construct(AuthorizationServer $server, TokenRepository $tokens, Parser $jwt, TeamModelIDService $teamModelIDService)
    {
        parent::__construct($server, $tokens, $jwt);

        $this->teamModelIDService = $teamModelIDService;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Illuminate\Http\Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        $response = $this->withErrorHandling(function () use ($request) {
            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
        });

        // 綁定 Team Model ID
        return $this->withErrorHandling(function () use ($request, $response) {
            if ($response->getStatusCode() !== 200) {
                return $response;
            }

            // 檢查 Request 有沒有傳 Habook ID，沒有傳則直接回傳 Response
            $requestParameters = (array)$request->getParsedBody();
            if (empty($requestParameters['habook_id'])) {
                return $response;
            }
            $habookID = $requestParameters['habook_id'];

            // 檢查欄位格式
            $validator = app('validator')->make(['habook_id' => $habookID], ['habook_id' => ['required', 'max:128']]);
            if ($validator->fails()) {
                throw new OAuthServerException(
                    '',
                    CommonExceptionCode::VALIDATION_FAILED,
                    '',
                    422
                );
            }

            // 解 access token 取 Member ID
            $json = json_decode((string)$response->getContent(), true);
            $jwt = $json['access_token'];
            $token = (new Parser())->parse($jwt);
            $memberID = $token->getClaim('sub');

            // MemberID 已經被綁定
            if ($this->teamModelIDService->isBindingByMemberID($memberID)) {
                throw new OAuthServerException(
                    '',
                    OAuthExceptionCode::MEMBER_ID_ALREADY_BINDING,
                    '',
                    400
                );
            }

            // Team Model ID 已經被綁定
            if ($this->teamModelIDService->isBindingByTeamModelID($habookID)) {
                throw new OAuthServerException(
                    '',
                    OAuthExceptionCode::TEAM_MODEL_ID_ALREADY_BINDING,
                    '',
                    400
                );
            }

            try {
                // 綁定 Member ID
                $this->teamModelIDService->doBinding($memberID, $habookID);
                return $response;
            } catch (QueryException $e) {
                throw new OAuthServerException(
                    '',
                    OAuthExceptionCode::SERVER_ERROR,
                    '',
                    500
                );
            } catch (\Exception $e) {
                throw new OAuthServerException(
                    '',
                    OAuthExceptionCode::SERVER_ERROR,
                    '',
                    500
                );
            }
        });
    }
}