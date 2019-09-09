<?php
/** @noinspection PhpDocSignatureInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Systemauthority;
use App\Repositories\Admin\AllInfoRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;


class TeacherController extends Controller
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
        //學校名稱
        $schoolID = $this->AllInfoRepository->getSchoolID();

        $teachers = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'T');
        })->where('SchoolID', $schoolID)->select('MemberID', 'RealName', 'Email', 'LoginID')->get();


        return view('adminSystem.account.teacher.index', compact('data', 'teachers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('adminSystem.account.teacher.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //學校名稱
        $schoolID = $this->AllInfoRepository->getSchoolID();

        //驗證
        // dd($request->all());
        $request->validate([
            'LoginID'  => 'required|min:4|unique:member,LoginID|alpha_num|alpha_dash',
            'RealName' => 'required',
            'password' => 'required|required_with:password_confirmation|confirmed|min:6',
            'Email'    => 'required|email',
            'Gender'   => 'required',
        ]);
        //新增當下時間
        $nowTime = Carbon::now();
        //新增老師
        $member = Member::query()->create([
            'LoginID'      => $request->LoginID,
            'RealName'     => $request->RealName,
            'Password'     => sha1($request->password),
            'Email'        => $request->Email,
            'Gender'       => $request->Gender,
            'SchoolID'     => $schoolID,
            'RegisterTime' => $nowTime,
        ]);

        // 新增老師身份
        $member->Systemauthority()->create([
            'IDLevel' => 'T',
            'SDate'   => $nowTime,
            'EDate'   => $nowTime,
        ]);

        return redirect()->route('teacher');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($memberID)
    {
        /*有關連systemauthority*/
        $memberData = Member::query()->find($memberID);

        $data = [
            'RealName'     => $memberData->RealName,
            'NickName'     => $memberData->NickName,
            'Password'     => $memberData->Password,
            'Gender'       => $memberData->Gender,
            'Birthday'     => $memberData->Birthday,
            'Email'        => $memberData->Email,
            'Department'   => $memberData->Department,
            'Telephone'    => $memberData->Telephone,
            'cellphone'    => $memberData->cellphone,
            'Address'      => $memberData->Address,
            'LoginID'      => $memberData->LoginID,
            'MaterialSize' => $memberData->systemauthority->MaterialSize,
            'EzcmsSize'    => $memberData->systemauthority->EzcmsSize,
        ];
        /*回傳JSON格式*/
        return response()->json($data, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($memberID)
    {
        $data = Member::query()->find($memberID);


        return view('adminSystem.account.teacher.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $memberID)
    {
        //驗證
        $request->validate([
            'RealName'     => 'required',
            'Password'     => 'nullable|min:6',
            'Email'        => 'required|email',
            'Gender'       => 'required',
            'MaterialSize' => 'nullable|numeric',
            'EzcmsSize'    => 'numeric',
        ]);

        $data               = [];
        $data['RealName']   = $request->RealName;
        $data['NickName']   = $request->NickName;
        $data['Gender']     = $request->Gender;
        $data['Birthday']   = $request->Birthday;
        $data['Email']      = $request->Email;
        $data['Department'] = $request->Department;
        $data['Telephone']  = $request->Telephone;
        $data['cellphone']  = $request->cellphone;
        $data['Address']    = $request->Address;


        if ($request->Password) {
            $data['Password'] = sha1($request->Password);
        }
        //更新老師資訊
        Member::query()->find($memberID)->update($data);
        //Systemauthority Table 使用空間更新
        Systemauthority::query()->find($memberID)->update([
            'MaterialSize' => $request->MaterialSize,
            'EzcmsSize'    => $request->EzcmsSize,
        ]);

        return redirect()->route('teacher');
        // return response()->json('success', 200);
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
