<?php

namespace App\Http\Controllers\Passport;

use Exception;
use Throwable;
use Illuminate\Http\Response;
use Zend\Diactoros\Response as Psr7Response;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors as PassportHandlesOAuthErrors;

/**
 * Trait HandlesOAuthErrors
 *
 * @package App\Http\Controllers\Passport
 */
trait HandlesOAuthErrors
{
    use PassportHandlesOAuthErrors;

    /**
     * Perform the given callback with exception handling.
     *
     * @param  \Closure $callback
     *
     * @return \Illuminate\Http\Response
     */
    public function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (OAuthServerException $e) {
            $this->exceptionHandler()->report($e);

            $exceptionResponse = $e->generateHttpResponse(new Psr7Response);
            if ($exceptionResponse->getStatusCode() === 302) {
                return $this->convertResponse($exceptionResponse);
            }

            $errorResponse = new ErrorHttpResponse($e);

            return $this->convertResponse($errorResponse->generateHttpResponse(new Psr7Response));
        } catch (Exception $e) {
            $this->exceptionHandler()->report($e);

            return new Response($e->getMessage(), 500);
        } catch (Throwable $e) {
            $this->exceptionHandler()->report(new FatalThrowableError($e));

            return new Response($e->getMessage(), 500);
        }
    }
}