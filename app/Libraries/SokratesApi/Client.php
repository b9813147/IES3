<?php

namespace App\Libraries\SokratesApi;

use App\Libraries\SokratesApi\Api\ApiInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

/**
 * Class Sokrates Service.
 *
 * @method Api\Contests createUsers()
 *
 * @package App\Libraries\Sokrates
 */
class Client
{
    /**
     * @var string
     */
    private $apiVersion;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * Client constructor.
     *
     * @param string|null $token
     * @param string|null $apiVersion
     *
     * @param $baseUrl
     */
    public function __construct($baseUrl, $token = null, $apiVersion = null)
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        if (!is_null($token)) {
            $stack->push($this->authenticate($token));
        }

        $this->apiVersion = $apiVersion ?: 'v1';

        $this->httpClient = new HttpClient([
            'base_uri' => $baseUrl,
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => sprintf('application/vnd.sokradeo.%s+json', $this->apiVersion)
            ],
            'handler' => $stack
        ]);
    }

    /**
     * Request Sokrates API.
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
            case 'contests':
                $api = new Api\Contests($this);
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
                $request = $request->withAddedHeader('Authorization', sprintf('Bearer %s', $token));
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