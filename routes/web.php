<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Entities\CourseEntity;
use Illuminate\Support\Facades\Redis;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ies3', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

// Authentication Routes...
Route::get('login', function () {
    return abort(404);
})->name('login');
Route::post('login', function () {
    return abort(404);
});
Route::post('logout', function () {
    return abort(404);
})->name('logout');

// TEAM Model Login
Route::get('tm_login', 'Auth\TeamModelLoginController@showLoginForm')->name('tm_login');
Route::post('tm_login', 'Auth\TeamModelLoginController@login');
Route::post('register_activation_code', 'Auth\RegisterActivationCodeController@register')->name('register_activation_code');
Route::group(['middleware' => ['auth.team_model.ticket','web', 'auth:web_all_status']], function ($router) {
    $router->get('oauth/authorize', [
        'uses' => 'Passport\AuthorizationController@authorize',
    ]);
});


// Registration Routes...
Route::get('register', function () {
    return abort(404);
})->name('register');

Route::post('register', function () {
    return abort(404);
});

// Password Reset Routes...
Route::get('password/reset', function () {
    return abort(404);
})->name('password.request');

Route::post('password/email', function () {
    return abort(404);
})->name('password.email');

Route::get('password/reset/{token}', function () {
    return abort(404);
})->name('password.reset');

Route::post('password/reset', function () {
    return abort(404);
});

// 區級 Dashboard
Route::get('districts/dashboard', function () {
    return File::get(public_path() . '/html/districts/dashboard/index.html');
});
Route::get('/school', 'YunHeApi\RenderController@school');
Route::get('/districts', 'YunHeApi\RenderController@getDistricts');
Route::get('/getToken', 'Admin\MemberController@getTestApiToken');


