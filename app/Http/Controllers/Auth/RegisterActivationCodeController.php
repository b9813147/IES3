<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use App\Services\TeamModel\TeamModelIDService;
use App\Services\UserCreateService;
use App\Services\SchoolService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Supports\AuthorizationSupport;
use App\Exceptions\SchoolNotFoundException;
use App\Exceptions\SchoolExpiredException;
use App\Exceptions\SchoolActivationCodeExpiredException;
use App\Exceptions\SchoolActivationCodeMemberLimitExceededException;

class RegisterActivationCodeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Activation Code Controller
    |--------------------------------------------------------------------------
    |
    | 使用啟用碼建立使用者帳號，目前僅供 TEAM Model ID 使用
    |
    */

    use RegistersUsers;
    use AuthorizationSupport;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * @var TeamModelIDService
     */
    protected $teamModelIDService;

    /**
     * @var UserCreateService
     */
    protected $userCreateService;

    /**
     * @var SchoolService
     */
    protected $schoolService;

    /**
     * Create a new controller instance.
     *
     * @param TeamModelIDService $teamModelIDService
     * @param UserCreateService $userCreateService
     * @param SchoolService $schoolService
     *
     * @return void
     */
    public function __construct(TeamModelIDService $teamModelIDService, UserCreateService $userCreateService, SchoolService $schoolService)
    {
        $this->middleware('guest');
        $this->teamModelIDService = $teamModelIDService;
        $this->userCreateService = $userCreateService;
        $this->schoolService = $schoolService;
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        // 檢查啟用碼
        try {
            $schoolInfo = $this->schoolService->getSchoolByActivationCode($request->input('activation_code'));
        } catch (SchoolNotFoundException $e) {
            return $this->sendFailedRegisterResponse(['activation_code' => [trans('auth.school.activation_code.not_found')]]);
        } catch (SchoolExpiredException $e) {
            return $this->sendFailedRegisterResponse(['activation_code' => [trans('auth.school.authorization.expired')]]);
        } catch (SchoolActivationCodeExpiredException $e) {
            return $this->sendFailedRegisterResponse(['activation_code' => [trans('auth.school.activation_code.expired')]]);
        } catch (SchoolActivationCodeMemberLimitExceededException $e) {
            return $this->sendFailedRegisterResponse(['activation_code' => [trans('auth.school.activation_code.member_limit_exceeded')]]);
        } catch (\Exception $e) {
            return $this->sendFailedRegisterResponse(['activation_code' => [trans('auth.system_failed')]]);
        }

        // 取 TEAM Model ID
        $teamModelId = $request->session()->get('habook.oauth.teamModelId', null);
        if (empty($teamModelId)) {
            return $this->sendFailedRegisterResponse(['team_model_id' => [trans('auth.team_model_id.not_found', ['team_model_id' => $teamModelId])]]);
        }

        // Team Model ID 已經被綁定
        if ($this->teamModelIDService->isBindingByTeamModelID($teamModelId)) {
            return $this->sendFailedRegisterResponse(['team_model_id' => [trans('auth.team_model_id.binding_already', ['team_model_id' => $teamModelId])]]);
        }

        // 建立帳號
        $user = $this->userCreateService->createUserForTeacher([
            'LoginID' => $teamModelId,
            'RealName' => $request->session()->get('habook.oauth.teamModelUserName', 'Teacher'),
            'SchoolID' => $schoolInfo->SchoolID,
            'Status' => 0,
            'activation_code_id' => $schoolInfo->id
        ]);

        if (empty($user)) {
            return $this->sendFailedRegisterResponse(['team_model_id' => [trans('auth.system_failed', ['team_model_id' => $teamModelId])]]);
        }

        $this->guard()->login($user);

        return $this->sendRegisterResponse($request);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'activation_code' => [
                'required',
                'string',
                'max:50',
            ]
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param $data
     */
    protected function sendFailedRegisterResponse($data)
    {
        throw ValidationException::withMessages($data);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRegisterResponse(Request $request)
    {
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('web_all_status');
    }
}
