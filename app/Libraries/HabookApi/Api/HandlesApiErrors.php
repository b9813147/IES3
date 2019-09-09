<?php

namespace App\Libraries\HabookApi\Api;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;

/**
 * Trait HandlesApiErrors
 *
 * @package App\Libraries\HabookApi\Api
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
     * @throws \App\Libraries\HabookApi\Exception\ClientException
     * @throws \App\Libraries\HabookApi\Exception\RequestException
     * @throws \App\Libraries\HabookApi\Exception\ServerException
     */
    public function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (ServerException $e) {
            throw new \App\Libraries\HabookApi\Exception\ServerException();
        } catch (ClientException $e) {
            throw new \App\Libraries\HabookApi\Exception\ClientException();
        } catch (RequestException $e) {
            throw new \App\Libraries\HabookApi\Exception\RequestException();
        } catch (Exception $e) {
            throw new Exception();
        }
    }
}