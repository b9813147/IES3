<?php

namespace App\Http\Controllers\Admin;

use App\Entities\CourseEntity;
use App\Entities\MemberEntity;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return string
     */
    public function getTestApiToken(Request $request)
    {
//        $user =  MemberEntity::query()->find(8606);
        $user=Member::find(8606);
//        dd($user);
        try {
            $token = $user->createToken('ares-test')->accessToken;
            return $token;
        } catch (\Exception $e) {
            dd(\Log::getMonolog(),$e->getMessage());
        }

//        $token = $user->createToken('test')->accessToken;

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
