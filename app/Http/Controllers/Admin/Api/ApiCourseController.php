<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Classpower;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiCourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $CourseNO=$request->CourseNO;
        //協同老師ph
        $classPowers = Classpower::select('ClassID', 'MemberID', 'ser')->where('ClassID', $CourseNO)->get();

        //判斷老師身份及狀態
        $members = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })->select('MemberID', 'RealName')->where('Status', 1)->get();

        $data = [
            'classPowers' => $classPowers,
            'members'     => $members,
        ];
        return response()->json($data, 200);
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
