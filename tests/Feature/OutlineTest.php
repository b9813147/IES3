<?php

namespace Tests\Feature;

use App\Models\Classpower;
use App\Models\Course;
use App\Models\Fc_outline;
use App\Models\Fc_purview;
use App\Models\Member;
use App\Models\SemesterInfo;
use App\Repositories\SemesterInfoRepository;
use App\Repositories\Fc_outlineRepository;
use App\Repositories\Fc_purviewRepository;
use App\Services\Fc_outlineService;
use App\Services\SemesterService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Container\Container as Application;

class OutlineTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
//        $t = Redis::KEYS('*');
        $memberID = 327907;
//        $teamModelId = '0934161322#413';
//       $t= Member::query()->where('LoginID',$teamModelId)->exists();
//        dd($t);

//        $schoolID= Member::query()->where('MemberID',$memberID)->pluck('SchoolID');
//        $getSharedCourses = Course::query()
//            ->where(['shared' => 1, 'SchoolID' => $schoolID,])
//            ->where('MemberID', '!=', $memberID)
//            ->pluck('CourseNO');

//        // 公開的課例
//        $fc_outline = Fc_outline::query()->select('id')
//            ->where('publicflag', 'E')
//            ->get();
//
//        // 取出公開課綱
//        $outline = Fc_outline::query()->select('id')
//            ->where('publicflag', 'E')
//            ->pluck('id');
//
//        // 分享名單
//        $preview = Fc_purview::query()->select('fc_outline_id')->where('MemberID', $member)->pluck('fc_outline_id');
//
//        // 檢查 是否有被加入分享過
//        if (collect($preview)->isEmpty()) {
//            /* add preview  default E*/
//            return $fc_outline->each(function ($item) use ($member) {
//                Fc_purview::query()->insert([
//                    'MemberID'      => $member,
//                    'fc_outline_id' => $item->id,
//                    'purview'       => 'R'
//                ]);
//            });
//        }
//
//        //判斷是否還有未加入的課綱
//        if ($outline->diff($preview)->isNotEmpty()) {
//            // 尚未被加入的課綱
//            $outline->diff($preview)->each(function ($id) use ($member) {
//                Fc_purview::query()->insert([
//                    'MemberID'      => $member,
//                    'fc_outline_id' => $id,
//                    'purview'       => 'R'
//                ]);
//            });
//            return;
//        }

//        $fc_purviewService = new Fc_purviewRepository(new Fc_purview);
//        $fc_outlineService = new Fc_outlineRepository(new Fc_outline);
//        dd(collect($fc_purviewService->getFcPurviewID($memberID))->isEmpty());
//        Course::query()->where('SNO')
//        if (collect($fc_purviewService->getFcPurviewID($memberID))->isEmpty()) {
//            $fc_outlineService->getPublicOutlineId()->each(function ($item) use ($memberID, $fc_purviewService) {
//                var_dump($item,$memberID,$fc_purviewService);
//              dd($fc_purviewService->AddPurview($memberID, $item->id));
//                $fc_purviewService->AddPurview($memberID, $item->id);
//            });
//        }
        //        $this->assertTrue(true);

        $member  = Member::query()->findOrFail($memberID);
        $courses = Course::query()
            ->where([
                'shared'   => 1,
                'SchoolID' => $member->SchoolID,
                ['MemberID', '!=', $memberID]
            ])
            ->pluck('CourseNO');

        dd($courses);


        $semester = (object)[
            'SNO'          => 22,
            'AcademicYear' => 2019,
            'Sorder'       => "0",
            'SchoolID'     => 0,
        ];
//
////        $memberID = 2;
        $member = Member::query()->where('MemberID', $memberID)->first();
        // 老師課程
        $getMemberCourseBySNO = Course::query()->Where(['MemberID' => $memberID, 'SNO' => $semester->SNO])->get();

        // 協同分享課程
        $getSharedCourses = Course::query()
            ->where(['shared' => 1, 'SchoolID' => $member->SchoolID,])
            ->where('MemberID', '!=', $memberID)
            ->pluck('CourseNO');

        // 協同老師
        $member_ClassPower = Classpower::query()->where('MemberID', $member->MemberID)->pluck('ClassID');

        //  判斷有無分享課程  及 這個老師 沒有這學期的課程
        if ($getMemberCourseBySNO->isEmpty() && !$getSharedCourses->isEmpty()) {
            // 判斷協同老師有被加入過
            if (!$member_ClassPower->isEmpty()) {
                $getSharedCourses->diff($member_ClassPower)->each(function ($item) use ($semester, $member) {
                    Classpower::query()->insert([
                        'AcademicYear' => $semester->AcademicYear,
                        'Sorder'       => $semester->Sorder,
                        'classtype'    => '2',
                        'ClassID'      => $item,
                        'powertype'    => 'Z',
                        'MemberID'     => $member->MemberID,
                    ]);
                });
                return;
            }

            $getSharedCourses->each(function ($item) use ($semester, $member) {
                Classpower::query()->insert([
                    'AcademicYear' => $semester->AcademicYear,
                    'Sorder'       => $semester->Sorder,
                    'classtype'    => '2',
                    'ClassID'      => $item,
                    'powertype'    => 'Z',
                    'MemberID'     => $member->MemberID,
                ]);
            });
            return;
        }
    }

    // 檢查是否被加入
//        dd($member_ClassPower->isEmpty());
//        dd(Classpower::query()->where('MemberID', $memberID)->pluck('ClassID')->isEmpty());

    // 檢查是否重複
}