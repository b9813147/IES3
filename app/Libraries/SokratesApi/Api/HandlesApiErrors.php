<?php

namespace App\Libraries\SokratesApi\Api;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;

/**
 * Trait HandlesApiErrors
 *
 * @package App\Libraries\SokratesApi\Api
 */
trait HandlesApiErrors
{
    /**
     * Perform the given callback with exception handling.
     *
     * @param $callback
     *
     * @return mixed
     *
     * @throws Exception
     * @throws \App\Libraries\SokratesApi\Exception\ClientException
     * @throws \App\Libraries\SokratesApi\Exception\RequestException
     * @throws \App\Libraries\SokratesApi\Exception\ServerException
     */
    public function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (ServerException $e) {
            throw new \App\Libraries\SokratesApi\Exception\ServerException();
        } catch (ClientException $e) {
            throw new \App\Libraries\SokratesApi\Exception\ClientException();
        } catch (RequestException $e) {
            throw new \App\Libraries\SokratesApi\Exception\RequestException();
        } catch (Exception $e) {
            throw new Exception();
        }
    }
}