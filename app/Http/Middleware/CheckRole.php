<?php

namespace App\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Helpers;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckRole
{
    use Helpers;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$role)
    {
        // 使用者資料
        $user = $this->auth->user();

        // 檢查老師身份
        if (in_array('teacher', $role)) {
            if ($user->IDLevel !== 'T') {
                throw new AccessDeniedHttpException();
            }
        }

        return $next($request);
    }
}