<?php

namespace App\Http\Controllers\Passport;

use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\ExceptionCodes\OAuthExceptionCode;

/**
 * Class ErrorHttpResponse
 *
 * 將 \League\OAuth2\Server\Exception\OAuthServerException 產生的 Exception 重新自訂回傳格式
 *
 * @package App\Http\Controllers\Passport
 */
class ErrorHttpResponse
{
    /**
     * @var array
     */
    private $headers;

    /**
     * @var int
     */
    private $httpStatusCode;

    /**
     * @var string
     */
    private $errorType;

    /**
     * @var int
     */
    private $errorCode;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var array
     */
    private $errorTypeList = [
        '2' => 'unsupportedGrantType',
        '3' => 'invalidRequest',
        '4' => 'invalidClient',
        '5' => 'invalidScope',
        '6' => 'invalidCredentials',
        '7' => 'serverError',
        '8' => 'invalidRefreshToken',
        '9' => 'accessDenied',
        '10' => 'invalidGrant',
    ];

    /**
     * ErrorHttpResponse constructor.
     *
     * @param OAuthServerException $exception
     */
    public function __construct(OAuthServerException $exception)
    {
        $this->headers = $exception->getHttpHeaders();
        $this->errorType = $exception->getErrorType();
        $this->httpStatusCode = $exception->getHttpStatusCode();
        $this->errorCode = $exception->getCode();
        $this->errorMessage = $exception->getMessage();

        // 改寫 \League\OAuth2\Server\Exception\OAuthServerException 產生的 Exception 資料
        if (isset($this->errorTypeList[$exception->getCode()])) {
            $method = $this->errorTypeList[$exception->getCode()];
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     *  Unsupported grant type error.
     */
    private function unsupportedGrantType()
    {
        $this->httpStatusCode = 400;
        $this->errorCode = OAuthExceptionCode::UNSUPPORTED_GRANT_TYPE;
        $this->errorMessage = 'The authorization grant type is not supported by the authorization server.';
    }

    /**
     * Invalid request error.
     */
    private function invalidRequest()
    {
        $this->httpStatusCode = 400;
        $this->errorCode = OAuthExceptionCode::INVALID_REQUEST;
        $this->errorMessage = 'The request is missing a required parameter, includes an invalid parameter value, ' .
            'includes a parameter more than once, or is otherwise malformed.';
    }

    /**
     * Invalid client error.
     */
    private function invalidClient()
    {
        $this->httpStatusCode = 401;
        $this->errorCode = OAuthExceptionCode::INVALID_CLIENT;
        $this->errorMessage = 'Client authentication failed';
    }

    /**
     * Invalid scope error.
     */
    private function invalidScope()
    {
        $this->httpStatusCode = 400;
        $this->errorCode = OAuthExceptionCode::INVALID_SCOPE;
        $this->errorMessage = 'The requested scope is invalid, unknown, or malformed';
    }

    /**
     * Invalid credentials error.
     */
    private function invalidCredentials()
    {
        $this->httpStatusCode = 401;
        $this->errorCode = OAuthExceptionCode::INVALID_CREDENTIALS;
        $this->errorMessage = 'The user credentials were incorrect.';
    }

    /**
     * Server error.
     */
    private function serverError()
    {
        $this->httpStatusCode = 500;
        $this->errorCode = OAuthExceptionCode::SERVER_ERROR;
        $this->errorMessage = 'The authorization server encountered an unexpected condition which prevented it from fulfilling.';
    }

    /**
     * Invalid refresh token.
     */
    private function invalidRefreshToken()
    {
        $this->httpStatusCode = 401;
        $this->errorCode = OAuthExceptionCode::INVALID_REFRESH_TOKEN;
        $this->errorMessage = 'The refresh token is invalid.';
    }

    /**
     * Access denied.
     */
    private function accessDenied()
    {
        $this->httpStatusCode = 401;
        $this->errorCode = OAuthExceptionCode::ACCESS_DENIED;
        $this->errorMessage = 'The resource owner or authorization server denied the request.';
    }

    /**
     * Invalid grant.
     */
    private function invalidGrant()
    {
        $this->httpStatusCode = 400;
        $this->errorCode = OAuthExceptionCode::INVALID_GRANT;
        $this->errorMessage = 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token '
            . 'is invalid, expired, revoked, does not match the redirection URI used in the authorization request, '
            . 'or was issued to another client.';
    }

    /**
     * Generate a HTTP response.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function generateHttpResponse(ResponseInterface $response)
    {
        $headers = $this->headers;

        foreach ($headers as $header => $content) {
            $response = $response->withHeader($header, $content);
        }

        $payload = [
            'error' => [
                'status_code' => $this->httpStatusCode,
                'code' => $this->errorCode,
                'message' => $this->errorMessage
            ]
        ];

        $response->getBody()->write(json_encode($payload));

        return $response->withStatus($this->httpStatusCode);
    }
}