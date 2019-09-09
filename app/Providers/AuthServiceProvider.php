<?php

namespace App\Providers;

use App\Extensions\TeamModelUserProvider;
use function foo\func;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Carbon\Carbon;
use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use App\Http\Controllers\Passport\AccessTokenController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\RequestGuard;
use App\Services\TeamModel\TeamModelTokenGuard;
use App\Services\TeamModel\TeamModelBearerTokenValidator;
use App\Extensions\Auth\IesUserProvider;
use App\Extensions\Auth\IesAllStatusTeacherProvider;
use App\Repositories\Eloquent\MemberRepository;
use App\Repositories\Eloquent\Oauth2MemberRepository;
use Laravel\Passport\Bridge\AccessTokenRepository;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    public function register()
    {
        $this->app->bind(
            'Laravel\Passport\Http\Controllers\AccessTokenController',
            'App\Http\Controllers\Passport\AccessTokenController'
        );
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Passport
        Passport::routes();
        Passport::tokensExpireIn(Carbon::now()->addDays(15));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));

        // 新增 IES 2 身份驗證方式
        Auth::provider('ies', function ($app, array $config) {
            return new IesUserProvider($this->app->make(MemberRepository::class), $config['model']);
        });

        // 新增 IES 2 身份驗證方式，包含所有狀態 Status 的老師
        Auth::provider('ies_all_status', function ($app, array $config) {
            return new IesAllStatusTeacherProvider($this->app->make(MemberRepository::class), $config['model']);
        });

        // 新增 Team Model Token Guard，專門驗證 Team Model Server 發出的 Token
        Auth::extend('team_model_token', function ($app, $name, array $config) {
            return new RequestGuard(function ($request) use ($config) {
                return (new TeamModelTokenGuard(
                    $this->makeResourceServer(),
                    Auth::createUserProvider($config['provider']),
                    $this->app->make(Oauth2MemberRepository::class)
                ))->user($request);
            }, $this->app['request']);
        });
    }

    /**
     * Create a ResourceServer instance
     *
     * @return ResourceServer
     */
    protected function makeResourceServer()
    {
        return new ResourceServer(
            $this->app->make(AccessTokenRepository::class),
            $this->makeCryptKey(config('ies.oauth_team_model_public_key_file_name')),
            new TeamModelBearerTokenValidator()
        );
    }

    /**
     * Create a CryptKey instance without permissions check
     *
     * @param string $key
     * @return \League\OAuth2\Server\CryptKey
     */
    protected function makeCryptKey($key)
    {
        return new CryptKey(
            'file://' . Passport::keyPath($key),
            null,
            false
        );
    }
}
