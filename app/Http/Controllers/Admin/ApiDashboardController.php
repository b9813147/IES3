<?php

namespace App\Http\Controllers\Admin;

use Dingo\Api\Routing\Helpers;
use App\Models\Course;
use App\Models\Member;
use App\Http\Controllers\Controller;

class ApiDashboardController extends Controller
{
    use Helpers;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        /*正式*/
//        $member = $this->auth->user();
        $member = 9022;

        /*取出該老師的學校代碼*/
        $school = Member::query()->select('MemberID')->find($member, 'MemberID')->value('SchoolID');
        /*計算老師數量*/
        $teacher = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'T');
        })->where('SchoolID', '=', $school)->select('MemberID')->count('MemberID');
        /*計算學生*/
        $student = Member::query()->whereHas('Systemauthority', function ($query) {
            $query->select('IDLevel')->where('IDLevel', '=', 'S');
        })->where('SchoolID', '=', $school)->select('MemberID')->count('MemberID');
        /*計算課程*/
        $course = Course::query()->select('CourseNO')->where('SchoolID', '=', $school)->count('CourseNO');
        /*計算硬碟空間*/
        $storeage = intval(disk_free_space('/') / 1024 / 1024 / 1024);
        $data     = [
            'teacher' => $teacher,
            'student' => $student,
            'course'  => $course,
            'store'   => $storeage,
        ];
        return response()->json([$data, 'success' => '成功']);
    }

}
