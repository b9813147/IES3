<?php

namespace App\Services\TeamModel;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Team Model Token 解析器
 *
 * @package App\Services\TeamModel
 */
class TeamModelBearerTokenValidator implements AuthorizationValidatorInterface
{
    use CryptTrait;

    /**
     * @var \League\OAuth2\Server\CryptKey
     */
    protected $publicKey;

    /**
     * Set the public key
     *
     * @param \League\OAuth2\Server\CryptKey $key
     */
    public function setPublicKey(CryptKey $key)
    {
        $this->publicKey = $key;
    }

    /**
     * Determine the access token in the authorization header and append OAUth properties to the request
     *  as attributes.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface|static
     *
     * @throws OAuthServerException
     */
    public function validateAuthorization(ServerRequestInterface $request)
    {
        if ($request->hasHeader('authorization') === false) {
            throw OAuthServerException::accessDenied('Missing "Authorization" header');
        }

        $header = $request->getHeader('authorization');
        $jwt = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header[0]));

        try {
            // Attempt to parse and validate the JWT
            $token = (new Parser())->parse($jwt);
            if ($token->verify(new Sha256(), $this->publicKey->getKeyPath()) === false) {
                throw OAuthServerException::accessDenied('Access token could not be verified');
            }

            // Return the request with additional attributes
            return $request->withAttribute('oauth_team_model_id', $token->getClaim('id'));
        } catch (\InvalidArgumentException $exception) {
            // JWT couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied($exception->getMessage());
        } catch (\RuntimeException $exception) {
            //JWR couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied('Error while decoding to JSON');
        }
    }
}
