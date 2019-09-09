<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Repositories\Admin\AllInfoRepository;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Input;
use Symfony\Component\Console\Tests\Input\InputTest;

class StudentController extends Controller
{
    protected $AllInfoRepository;

    public function __construct(AllInfoRepository $AllInfoRepository)
    {
        $this->AllInfoRepository = $AllInfoRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        //判斷老師身份及狀態
        $members = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'S');
        })->select('MemberID', 'RealName', 'LoginID', 'CivilID', 'RegisterTime', 'Status')
            ->where('Status', 1)->orderBy('MemberID', 'DESC')->paginate(10);
// dd($members);
        return view('adminSystem.account.student.index', compact('members'));
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $student = $request->memberID;
        $status  = $request->status;
        Member::query()->where('MemberID', $student)->update([
            'Status' => $status
        ]);
        return response()->json('success', 200);
        // dd($student);
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

    //API AJAX GETw
    public function dashboard()
    {
        $school = $this->AllInfoRepository->getSchoolID();

        //學生總人數
        $studentCount = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'S');
        })->where('SchoolID', '=', $school)->select('MemberID')->count('MemberID');
        //學生啟用
        $active = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'S');
        })->select('MemberID')->where('SchoolID', $school)->where('Status', 1)->count('MemberID');
        //學生停用
        $disable = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'S');
        })->select('MemberID')->where('SchoolID', $school)->where('Status', 0)->count('MemberID');

        $data = (object)[
            'studentCount' => $studentCount,
            'active'       => $active,
            'disable'      => $disable,
        ];

        return response()->json($data, 200);
    }

    public function filter(Request $request)
    {

        //轉格式
        $sDate = Carbon::parse(Input::get('startDate'))->toDateString();
        //原始資料判斷
        $startDate = (Input::get('startDate')) ? $sDate : false;

        //轉格式
        $eDate = Carbon::parse(Input::get('endDate'))->toDateString();
        //原始資料判斷
        $endDate = (Input::get('endDate')) ? $eDate : false;

        //模糊搜尋
        // $searche = (isset($request->searche)) ? $request->searche : false;
        $searche = Input::get('searche');

        //狀態 1 顯示啟用 2顯示停用
        $status = (Input::get('status') == 'on') ? '1' : '2';


        // dd($request->all());
        // print_r(Input::all());
        // dd(Input::All());
        if ($startDate && $endDate) {
            $members = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'S');
            })->select('MemberID', 'RealName', 'LoginID', 'CivilID', 'RegisterTime', 'Status')
                ->where('Status', $status)
                ->where('LoginID', 'LIKE', '%' . $searche . '%')
                ->where('CivilID', 'LIKE', '%' . $searche . '%')
                ->whereBetween('RegisterTime', [$startDate, $endDate])
                ->orderBy('MemberID', 'DESC')->paginate(10);
            $members->appends([
                'searche'   => $searche,
                'startDate' => $startDate,
                'endDate'   => $endDate,
                'status'    => $status,
            ]);
            print_r($members);
            // dd($members);
            // if (count($members) > 0) {
            return view('adminSystem.account.student.index', compact('members'));

            // }

        }

        // return view('adminSystem.account.student.index', compact('members'))->withMessage("No Results found!");
        /*elseif ($startDate) {

            $members = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'S');
            })->select('MemberID', 'RealName', 'LoginID', 'CivilID', 'RegisterTime', 'Status')
                ->where('Status', $status)
                ->where('LoginID', 'LIKE', '%' . $searche . '%')
                ->where('CivilID', 'LIKE', '%' . $searche . '%')
                ->where('RegisterTime', $startDate)
                ->orderBy('MemberID', 'DESC')->paginate(10);
            // dd($members);
            return view('adminSystem.account.student.index', compact('members'));
        } elseif ($endDate) {
            $members = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'S');
            })->select('MemberID', 'RealName', 'LoginID', 'CivilID', 'RegisterTime', 'Status')
                ->where('Status', $status)
                ->where('LoginID', 'LIKE', '%' . $searche . '%')
                ->where('CivilID', 'LIKE', '%' . $searche . '%')
                ->where('RegisterTime', $endDate)
                ->orderBy('MemberID', 'DESC')->paginate(10);
            // dd($members);
            return view('adminSystem.account.student.index', compact('members'));
        } else {
            $members = Member::query()->whereHas('Systemauthority', function ($query) {
                $query->select('IDLevel')->where('IDLevel', 'S');
            })->select('MemberID', 'RealName', 'LoginID', 'CivilID', 'RegisterTime', 'Status')
                ->where('Status', $status)
                ->where('LoginID', 'LIKE', '%' . $searche . '%')
                ->where('CivilID', 'LIKE', '%' . $searche . '%')
                ->orderBy('MemberID', 'DESC')->paginate(10);
            dd($members);
            return view('adminSystem.account.student.index', compact('members'));
        }*/

    }

    public function test()
    {
        try {
            return 'test';
        } catch (\Exception $exception) {
        }

    }
}
