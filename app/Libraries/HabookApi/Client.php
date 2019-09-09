<?php

namespace App\Libraries\HabookApi;

use App\Libraries\HabookApi\Api\ApiInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

/**
 * Class Habook Core Service.
 *
 * @package App\Libraries\HabookApi
 */
class Client
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * Client constructor.
     *
     * @param $baseUrl
     */
    public function __construct($baseUrl, $token = null)
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        if (!is_null($token)) {
            $stack->push($this->authenticate($token));
        }

        $this->httpClient = new HttpClient([
            'base_uri' => $baseUrl,
            'handler' => $stack
        ]);
    }

    /**
     * Request Core Service API.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ApiInterface
     */
    public function api($name)
    {
        switch ($name) {
            case 'apps':
                $api = new Api\Apps($this);
                break;
            case 'me':
                $api = new Api\CurrentUser($this);
                break;
            default:
                throw new \InvalidArgumentException();
        }

        return $api;
    }

    /**
     * Add Authorization header for request.
     *
     * @param string $token
     *
     * @return \Closure
     */
    public function authenticate($token)
    {
        return function (callable $handler) use ($token) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $token) {
                $request = $request->withHeader('Authorization', $token);
                return $handler($request, $options);
            };
        };
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }
}