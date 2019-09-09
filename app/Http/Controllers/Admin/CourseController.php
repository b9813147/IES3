<?php

namespace App\Http\Controllers\Admin;

use App\Models\Classinfo;
use App\Models\Classpower;
use App\Models\Course;
use App\Models\Member;
use App\Models\SchoolInfo;
use App\Repositories\Admin\AllInfoRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourseController extends Controller
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
        //判斷學期
        $semester = $this->AllInfoRepository->getSemester();
        //學校ID
        $schooID = $this->AllInfoRepository->getSchoolID();

        //判斷這學學期所屬課程
        $courses = Course::with('Member')->where('SNO', $semester->SNO)->where('SchoolID', $schooID)->get();
        //判斷學校名稱
        $SchoolName = SchoolInfo::select('SchoolID', 'SchoolName')->where('SchoolID',
            $schooID)->first()->value('SchoolName');


        return view('adminSystem.account.course.index', compact('SchoolName', 'courses', 'semester'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //判斷學期
        $semester = $this->AllInfoRepository->getSemester();

        //判斷學校狀態
        // $schoolStatus = SchoolInfo::select('SchoolID', 'Status')->where('Status', 1)->get();
        //學校ID
        $SchoolID = $this->AllInfoRepository->getSchoolID();

        //判斷老師身份
        $teachers = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'T');
        })->select('MemberID', 'RealName', 'SchoolID')->where('SchoolID', $SchoolID)->get();

        //學校名稱
        $SchoolName = SchoolInfo::select('SchoolID', 'SchoolName')->where('SchoolID',
            $SchoolID)->first()->value('SchoolName');


        return view('adminSystem.account.course.create', compact('teachers', 'semester', 'SchoolName'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // dd($request->all());


        //判斷學期
        $semester = $this->AllInfoRepository->getSemester();

        //學校名稱
        $schoolID = $this->AllInfoRepository->getSchoolID();

        //驗證
        $request->validate([
            'CourseName.*'      => 'required|unique:course,CourseName',
            'AssignedTeacher.*' => 'required',
        ]);

        foreach (array_keys($request->GradeName) as $i) {
            $CourseNO = Course::query()->max('CourseNO') + 1;

            //判斷年級
            $classinfos = Classinfo::all();
            foreach ($classinfos as $classinfo) {
                if ($classinfo->GradeName == $request->GradeName[$i]) {
                    Course::query()->create([
                        'CourseNO'    => $CourseNO,
                        'CourseCode'  => $request->CourseCode[$i],
                        'CourseName'  => $request->CourseName[$i],
                        //default Student CourseCount 0
                        'CourseCount' => 0,
                        'MemberID'    => $request->AssignedTeacher[$i],
                        //學期
                        'SNO'         => $semester->SNO,
                        //年級
                        'CNO'         => $classinfo->CNO,
                        'SchoolID'    => $schoolID,
                    ]);
                }
            }

        }
        return redirect()->route('course');
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
     * @param  int $CourseNO
     * @return \Illuminate\Http\Response
     */
    public function edit($CourseNO)
    {
        //取得學期
        $semester = $this->AllInfoRepository->getSemester();

        //取得學校ID
        $School = $this->AllInfoRepository->getSchoolID();

        //學校名稱
        $SchoolName = SchoolInfo::find($School)->value('SchoolName');

        // relation member table
        $course = Course::select('CourseNO', 'CourseCode', 'CourseName', 'MemberID', 'CourseCount')->where('CourseNO',
            $CourseNO)->get()->first();

        //協同老師
        $classPowers = Classpower::select('ClassID', 'MemberID', 'ser')->where('ClassID', $CourseNO)->get();

        //判斷老師身份及狀態
        $members = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })->select('MemberID', 'RealName')->where('Status', 1)->get();


        return view('adminSystem.account.course.edit',
            compact('SchoolName', 'semester', 'course', 'members', 'classPowers'));
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

    public function collaborative($CourseNO)
    {

        $semester = $this->AllInfoRepository->getSemester();

        //判斷老師身份及狀態
        $members = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', 'T');
        })->select('MemberID', 'RealName', 'LoginID')->where('Status', 1)->get();

        return view('adminSystem.account.course.collaborative', compact('semester', 'members', 'CourseNO'));
    }

    public function collaborativeStore(Request $request)
    {
        $semester = $this->AllInfoRepository->getSemester();
        $CourseNO = $request->CourseNO;
        // dd($request->all());
        if (isset($request->teacher)) {
            foreach (array_keys($request->teacher) as $key) {
                Classpower::query()->create([
                    'AcademicYear' => $semester->AcademicYear,
                    'Sorder'       => $semester->SOrder,
                    //固定式２
                    'classtype'    => 2,
                    'ClassID'      => $request->CourseNO,
                    //固定式Z
                    'powertype'    => 'Z',
                    'MemberID'     => $request->teacher[$key],
                ]);
            }
            return redirect()->route('course.edit', ['CourseNO' => $CourseNO]);
        } else {
            return redirect()->route('course.edit', ['CourseNO' => $CourseNO]);
        }
    }

    public function collaborativeDestroy(Request $request)
    {
// dd($request->all());
        // dd(\request()->all());
        // $t = Classpower::select('ser')->where('ser', $ser)->get();
        Classpower::query()->where('ser', $request->ser)->delete();
        // dd($request->all());
        return response()->json('success', 200);
    }
}
