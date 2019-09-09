<?php

namespace App\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Helpers;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckClientTeamModel
{
    use Helpers;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 檢查是否為 TEAM Model 專用的 client id，$request->input('oauth_client_id') 由 \App\Http\Middleware\CheckClientCredentials 寫入數值
        if (!in_array($request->input('oauth_client_id'), config('auth.clients.team_model.oauth_client_id'))) {
            throw new AccessDeniedHttpException();
        }

        return $next($request);
    }
}