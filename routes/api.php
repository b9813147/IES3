<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Dingo Router
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', [
    'namespace'  => 'App\Http\Controllers\Api\V1',
    'middleware' => ['api.throttle'],
    'limit'      => 5000,
    'expires'    => 0.5,
], function ($api) {
    $api->group(['middleware' => ['api.auth', 'role:teacher'], 'providers' => ['passport', 'team_model']], function ($api) {
        // 個人資料相關 API
        $api->group(['prefix' => 'me'], function ($api) {
            // 個人基本資料
            $api->get('/', ['as' => 'me.index', 'uses' => 'Users\UserController@index']);
        });

        // 學年學期相關 API
        $api->group(['prefix' => 'semesters'], function ($api) {
            // 學年學期資料清單
            $api->get('/', ['as' => 'semesters.index', 'uses' => 'Schools\SemesterController@index']);
        });

        // 課程相關 API
        $api->group(['prefix' => 'courses'], function ($api) {
            // 課程資料清單
            $api->get('/', ['as' => 'courses.index', 'uses' => 'Courses\CourseController@index']);

            // 課程邀請碼
            $api->put('/{courseNO}/majorcode', ['as' => 'courses.majorCode', 'uses' => 'Courses\CourseController@majorCode'])
                ->where(['courseNO' => '[0-9]{1,11}+']);

            // 課程建立
            $api->post('/create', ['as' => 'courses.createCourse', 'uses' => 'Courses\CourseController@createCourse']);

            // 查詢年級
            $api->get('/grade', ['as' => 'courses.gradeName', 'uses' => 'Courses\CourseController@getGrades']);

            // 課程編輯
            $api->put('{courseNO}/course', ['as' => 'courses.edit', 'uses' => 'Courses\CourseController@edit'])
                ->where(['courseNO' => '[0-9]{1,11}+']);

            // 學生資料清單編輯
            $api->put('{courseNO}/students', ['as' => 'courses.student.edit', 'uses' => 'Courses\StudentController@edit'])
                ->where(['courseNO' => '[0-9]{1,11}+']);

            // 某課程學生資料清單
            $api->get('/{courseNO}/students', ['as' => 'courses.students.index', 'uses' => 'Courses\StudentController@index'])
                ->where(['courseNO' => '[0-9]{1,11}+']);

            // 上傳課程學生頭像
            $api->post('/{courseNo}/students/{memberId}/avatar', ['as' => 'courses.students.avatar.store', 'uses' => 'Courses\StudentAvatarController@store'])
                ->where(['courseNo' => '[0-9]{1,11}+', 'memberId' => '[0-9]{1,11}+']);
        });

        // 電子紙條相關 API
        $api->group(['prefix' => 'messages'], function ($api) {
            // 電子紙條資料清單
            $api->get('/', ['as' => 'messages.index', 'uses' => 'Messages\MessageController@index']);

            // 某電子紙條附件下載
            $api->get('/{msgId}/attachments/{hashId}', ['as' => 'messages.attachments.index']);

            // 發送電子紙條
            $api->post('/', ['as' => 'messages.store', 'uses' => 'Messages\MessageController@store']);
        });
    });

    // OAuth 2.0 - Client Credentials Grant
    $api->group(['middleware' => ['client']], function ($api) {
        // 公司活動相關 API
        $api->group(['prefix' => 'events'], function ($api) {
            // 2018 醍摩豆大賽
            $api->post('/team_contest/2018', ['as' => 'team_contest_2018.index', 'uses' => 'Events\TeamContest2018Controller@store']);

            // 2019 醍摩豆大賽
            $api->post('/team_contest/2019', ['as' => 'team_contest_2019.index', 'uses' => 'Events\TeamContest2019Controller@store']);
        });

        // TEAM Model Service
        $api->group(['prefix' => 'team_model', 'middleware' => ['auth.client.team_model']], function ($api) {
            // 解除綁定 TEAM Model ID
            $api->delete('/users', ['as' => 'team_model.users.destroy', 'uses' => 'TeamModel\UserController@destroy']);

            // 更新或新增訂單
            $api->put('/orders', ['as' => 'team_model.orders.store', 'uses' => 'TeamModel\BigBlueOrderController@update'])->middleware('auth.core_service.web_hook.check_url');
        });
    });
});

app('Dingo\Api\Exception\Handler')->register(function (\Dingo\Api\Exception\UnknownVersionException $exception) {
    return Response::make(
        [
            'error' => [
                'status_code' => $exception->getStatusCode(),
                'code'        => \App\ExceptionCodes\OAuthExceptionCode::INVALID_VERSION,
                'message'     => $exception->getMessage()
            ]
        ], 400);
});

// Repository Exception 回傳 HTTP code 500
app('Dingo\Api\Exception\Handler')->register(function (\App\Exceptions\RepositoryException $exception) {
    throw new \Symfony\Component\HttpKernel\Exception\HttpException('');
});

app('Dingo\Api\Exception\Handler')->register(function (\Illuminate\Auth\AuthenticationException $exception) {
    return Response::make(
        [
            'error' => [
                'status_code' => '401',
                'code'        => \App\ExceptionCodes\OAuthExceptionCode::INVALID_ACCESS_TOKEN,
                'message'     => '401 Unauthorized'
            ]
        ], 401);
});

//對外測試API
Route::get('/menu', 'YunHeApi\RenderController@menu');
Route::get('/school', 'YunHeApi\RenderController@school');
Route::get('/districts', 'YunHeApi\RenderController@getDistricts');