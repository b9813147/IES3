<?php

namespace App\Libraries\HabookApi\Api;

use App\Libraries\HabookApi\Client;
use App\Libraries\HabookApi\Exception\RequestException;

/**
 * Abstract class for Api classes.
 *
 * @package App\Service\HabookCoreService\Api
 */
abstract class AbstractApi implements ApiInterface
{
    use HandlesApiErrors;

    /**
     * @var Client
     */
    protected $client;

    /**
     * AbstractApi constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send a POST request with JSON-encoded parameters.
     *
     * @param string $path Request path.
     * @param array $parameters POST parameters to be JSON encoded.
     * @param array $requestHeaders Request headers.
     *
     * @return array|string
     *
     * @throws \Exception
     * @throws \App\Libraries\HabookApi\Exception\ClientException
     * @throws \App\Libraries\HabookApi\Exception\RequestException
     * @throws \App\Libraries\HabookApi\Exception\ServerException
     */
    protected function post($path, array $parameters = [], array $requestHeaders = [])
    {
        return $this->postRaw(
            $path,
            $this->createJsonBody($parameters),
            $requestHeaders
        );
    }

    /**
     * Send a POST request with JSON-RPC-encoded parameters.
     *
     * @param string $path Request path.
     * @param string $method Request API method.
     * @param array $parameters POST parameters to be JSON encoded.
     * @param array $requestHeaders Request headers.
     *
     * @return array|string
     *
     * @throws \Exception
     * @throws \App\Libraries\HabookApi\Exception\ClientException
     * @throws \App\Libraries\HabookApi\Exception\RequestException
     * @throws \App\Libraries\HabookApi\Exception\ServerException
     */
    protected function postRpc($path, $method, array $parameters = [], array $requestHeaders = [])
    {
        return $this->postRaw(
            $path,
            $this->createJsonBody([
                'id' => '',
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $parameters
            ]),
            $requestHeaders
        );
    }

    /**
     * Send a POST request with raw data.
     *
     * @param string $path Request path.
     * @param string $body Request body.
     * @param array $requestHeaders Request headers
     *
     * @return array|string
     *
     * @throws \Exception
     * @throws \App\Libraries\HabookApi\Exception\ClientException
     * @throws \App\Libraries\HabookApi\Exception\RequestException
     * @throws \App\Libraries\HabookApi\Exception\ServerException
     */
    protected function postRaw($path, $body, array $requestHeaders = [])
    {
        $response = $this->withErrorHandling(function () use ($path, $body, $requestHeaders) {
            return $this->client->getHttpClient()->post(
                $path,
                [
                    'headers' => $requestHeaders,
                    'body' => $body
                ]
            );
        });

        return $this->getContent($response);
    }

    /**
     * Create a JSON encoded version of an array of parameters.
     *
     * @param array $parameters Request parameters
     *
     * @return null|string
     */
    protected function createJsonBody(array $parameters)
    {
        return (count($parameters) === 0) ? null : json_encode($parameters, empty($parameters) ? JSON_FORCE_OBJECT : 0);
    }

    /**
     * @param $response
     *
     * @return mixed
     *
     * @throws \App\Libraries\HabookApi\Exception\RequestException
     */
    public static function getContent($response)
    {
        $body = $response->getBody()->getContents();
        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $content = json_decode($body, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                if (!empty($content['error'])) {
                    throw new RequestException();
                }

                if (empty($content['result'])) {
                    throw new RequestException();
                }

                return $content['result'];
            }
        }

        throw new RequestException();
    }
}