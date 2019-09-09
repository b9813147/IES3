<?php

namespace App\Http\Middleware;

use Closure;
use App\Libraries\HabookApi\Client;
use Illuminate\Support\Facades\Auth;

class CheckTeamModelTicket
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->validateTicket($request);

        return $next($request);
    }

    /**
     * Validate the ticket on the request.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function validateTicket($request)
    {
        $requestTicket = $request->input('ticket');
        $sessionTicket = $request->session()->get('habook.oauth.teamModelTicket', null);

        // Session 有 Ticket 而且和 Request 的 Ticket 一樣則不需要在驗
        if (!empty($sessionTicket) && $sessionTicket == $requestTicket) {
            return true;
        }

        $request->session()->forget('habook.oauth');

        try {
            // 取 Core Service API Token
            $habook = new Client(config('habook.core_service_api.url'));
            $token = $habook->api('apps')->createRegisterToken(
                config('habook.core_service_api.client_id'),
                config('habook.core_service_api.verification_code'),
                config('habook.core_service_api.verification_code_ver')
            );

            // 驗證 ticket 並取使用者資料
            $habook = new Client(config('habook.core_service_api.url'), $token['token']);
            $user = $habook->api('me')->show($requestTicket);

            // 寫入 Session
            $request->session()->put('habook.oauth.teamModelTicket', $requestTicket);
            $request->session()->put('habook.oauth.teamModelId', $user['id']);
            $request->session()->put('habook.oauth.teamModelUserName', $user['name']);

            return true;
        } catch (\Exception $e) {
            Auth::guard('web_all_status')->logout();
            return abort(403, trans('auth.team_model_id.not_found'));
        }
    }
}
