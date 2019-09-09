<?php

namespace App\Libraries\SokratesApi;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Config\Repository as Config;

class ApiTokenFactory
{
    use InteractsWithTime;

    /**
     * The configuration repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create an API token factory instance.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     *
     * @return void
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Create a new API token.
     *
     * @param array $payload
     * @param int $lifetime JWT 到期時間需要延長幾分鐘
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($payload, $lifetime)
    {
        $expiration = Carbon::now()->addMinutes($lifetime);

        return $this->createToken($payload, $expiration);
    }

    /**
     * Create a new JWT token
     *
     * @param  array $payload
     * @param  \Carbon\Carbon $expiration
     *
     * @return string
     */
    protected function createToken($payload, Carbon $expiration)
    {
        $config = $this->config->get('sokrates');

        $payload = array_merge(['exp' => $expiration->getTimestamp()], $payload);

        return JWT::encode($payload, $config['contests_api']['jwt']['secret'], 'HS256');
    }
}