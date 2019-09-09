<?php /** @noinspection ALL */

namespace App\Http\Controllers\Admin\Api;

use App\Models\ApiDistricts;
use App\Models\District_schools;
use App\Models\DistrictsAllSchool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Supports\HashIdSupport;


// 設定給跨網域api 使用
header('Access-Control-Allow-Origin:*');
// 设置允许的响应类型
header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
// 设置允许的响应头
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class AllDateController extends Controller
{
    use HashIdSupport;

    /*
     * 選單內容
     * 提供可以選擇的時間範圍
     * 只取年份當上下學期
     * */
    public function menu()
    {

        $years = DistrictsAllSchool::query()->select('year')->where('year', '>=', 2016)->distinct()->get();
        $nowYear= Carbon::now()->format('Y');

        foreach ($years as $key => $year) {
            $time[] = [
                'key'   => 0,
                'value' => $year->year . '上半学期',
                'year'  => $year->year,
            ];

            if ($year->year < $nowYear)
                $time[] = [
                    'key'   => 1,
                    'value' => $year->year . '下半学期',
                    'year'  => $year->year,
                ];
        }

        //学期时间列表
        $semesterlist = [
            'state' => [
                'semesterlist' => [
                    "time" => $time
                ],
            ],
        ];


        return response()->json($semesterlist);
    }

    /**
     * 顯示單一學校
     * 學校ＩＤ必須傳入
     * 學期default 當下最新學期  學年  default 當下最新學年
     * $schoolID
     * $semester     0上學期 １下學期
     * ＊$year
     *
     */
    public function getSchool(Request $request)
    {
        // dd($request->all());
        $schoolId = null ?? $request->schoolId;
        $semester = null ?? $request->semester;
        $year     = null ?? $request->year;

        //判斷有無傳入時間，如果沒有傳入時間，就自動帶當下時間
        // $nowYear = (isset($year)) ? $year : DistrictsAllSchool::query()->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)->max('year');

        if ($schoolId && $semester && $year) {
            $data = DB::table('ApiDistricts')->selectRaw('
                   sum(teachnum)                 teachnum,
                   sum(studentnum)               studentnum,
                   sum(patriarchnum)             patriarchnum,
                   sum(curriculum)               curriculum,
                   sum(electronicalnote)         electronicalnote,
                   sum(uploadMovie)              uploadMovie,
                   sum(production)               production,
                   sum(overturnClass)            overturnClass,
                   sum(analogyTest)              analogyTest,
                   sum(onlineTest)               onlineTest,
                   sum(interClassCompetition)    interClassCompetition,
                   sum(HiTeach)                  HiTeach,
                   sum(performanceLogin)         performanceLogin,
                   sum(mergeActivity)            mergeActivity,
                   sum(onlineChecking)           onlineChecking,
                   sum(allLearningProcess)       allLearningProcess,
                   sum(personAge)                personAge,
                   sum(areaShare)                areaShare,
                   sum(schoolShare)              schoolShare,
                   sum(overallResourece)         overallResourece,
                   sum(totalresources)             totalresources,
                   sum(onlineTestComplete)       onlineTestComplete,
                   sum(productionPercentage)     productionPercentage')
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->get();

            /*
             * $schoolInfo
             * 學校名稱
             * 學校編號
             * */
            $schoolInfo = ApiDistricts::query()
                ->join('schoolinfo', 'schoolInfo.SchoolID', '=', 'ApiDistricts.schoolId')
                ->select('schoolInfo.SchoolName', 'schoolInfo.SchoolID', 'schoolInfo.Abbr')
                ->where('ApiDistricts.schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('ApiDistricts.semester', $semester)
                ->where('ApiDistricts.year', $year)
                ->distinct()
                ->get();


            //個案算平均值
            $percentageData = DB::table('ApiDistricts')->select('productionPercentage', 'onlineTestComplete')
                ->where('year', '!=', 0)
                ->where('onlineTestComplete', '!=', 0)
                ->where('productionPercentage', '!=', 0)
                ->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('semester', $semester)
                ->where('year', $year)
                ->get();

            //判段資料要超過0才會成立
            // if ($percentageData->toArray() != []) {
            if (count($percentageData)) {
                foreach ($percentageData as $item) {
                    $p[] = $item->productionPercentage;
                    $o[] = $item->onlineTestComplete;
                }
                $production = round(array_sum($p) / count($p));
                $onlineTest = round(array_sum($o) / count($o));
            } else {
                $production = 0;
                $onlineTest = 0;
            }

            $JsonData = [
                'schoolname'           => $schoolInfo[0]->SchoolName,                              //學校名稱
                'schoolid'             => $schoolInfo[0]->SchoolID,                                //學校ID，所有為all，學校為數字ID
                'schoolcode'           => $schoolInfo[0]->Abbr,                        //學校簡碼
                'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                'dashboard'            => [
                    'teachlogintable' => [                                          //老師登入
                        'num'      => 1,            //标识符
                        'loginnum' => $this->schoolDashboard('teachlogintable', $schoolId, $semester, $year)['num'],   //教师登录数
                        'time'     => $this->schoolDashboard('teachlogintable', $schoolId, $semester, $year)['time'],     //折线图X轴时间
                        'data'     => $this->schoolDashboard('teachlogintable', $schoolId, $semester, $year)['data'],         //数据
                        // $this->teachLoginTable(),
                    ],
                    'studylogintable' => [         //学生登录情况
                        'num'           => 1,
                        'studyloginnum' => $this->schoolDashboard('studylogintable', $schoolId, $semester, $year)['num'], //学生登录数
                        'time'          => $this->schoolDashboard('studylogintable', $schoolId, $semester, $year)['time'],//柱状图X轴时间
                        'data'          => $this->schoolDashboard('studylogintable', $schoolId, $semester, $year)['data'],//数据
                    ],
                    'smartclasstable' => [
                        'schoolmessage'   => [
                            'id'               => $schoolInfo[0]->SchoolID,                      //學校ID
                            'curriculum'       => $data[0]->curriculum,                           //課程總數
                            'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                            'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                            'production'       => $data[0]->production,                           //作業作品數
                            'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                        ],
                        'learningprocess' => [
                            'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                            'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                            'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                            'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                            'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                            'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                            'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                            'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                        ],
                        'chartdata'       => [
                            // $this->chartData(),
                            [
                                'title' => '课程总数',
                                "num"   => 1,
                                "time"  => $this->schoolChartData('curriculum', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('curriculum', $schoolId, $semester, $year)['data'],
                            ],
                            [
                                'title' => '电子笔记数',
                                "num"   => 2,
                                "time"  => $this->schoolChartData('electronicalnote', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('electronicalnote', $schoolId, $semester, $year)['data'],
                            ],
                            [
                                'title' => '上传影片数',
                                "num"   => 3,
                                "time"  => $this->schoolChartData('uploadmovie', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('uploadmovie', $schoolId, $semester, $year)['data'],
                            ],
                            [
                                'title' => '作业作品数',
                                "num"   => 4,
                                "time"  => $this->schoolChartData('production', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('production', $schoolId, $semester, $year)['data'],
                            ],
                            [
                                'title' => '翻转课堂数',
                                "num"   => 5,
                                "time"  => $this->schoolChartData('overturnClass', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('overturnClass', $schoolId, $semester, $year)['data'],
                            ],
                            [
                                'title' => '学习历程',
                                "num"   => 6,
                                "time"  => $this->schoolChartData('allLearningProcess', $schoolId, $semester, $year)['time'],
                                "data"  => $this->schoolChartData('allLearningProcess', $schoolId, $semester, $year)['data'],
                            ]
                            //智慧教堂應用下２個圖表
                        ],
                        'resource'        => [
                            'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                            'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                            'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                            'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                            'subjectnum'       => [                                   //題目數
                                [
                                    'id'    => 0,
                                    'title' => '  题目数',
                                    'num'   => $this->schoolDashboard('subjectnum', $schoolId, $semester, $year)['num'],
                                    'time'  => $this->schoolDashboard('subjectnum', $schoolId, $semester, $year)['time'],
                                    'data'  => $this->schoolDashboard('subjectnum', $schoolId, $semester, $year)['data'],
                                ],
                            ],
                            'examinationnum'   => [                                    //試卷數
                                [
                                    'id'    => 1,
                                    'title' => '试卷数',
                                    'num'   => $this->schoolDashboard('examinationnum', $schoolId, $semester, $year)['num'],
                                    'time'  => $this->schoolDashboard('examinationnum', $schoolId, $semester, $year)['time'],
                                    'data'  => $this->schoolDashboard('examinationnum', $schoolId, $semester, $year)['data'],
                                ],
                            ],
                            'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                [
                                    'id'    => 2,
                                    'title' => '教材数',
                                    'num'   => $this->schoolDashboard('textbooknum', $schoolId, $semester, $year)['num'],
                                    'time'  => $this->schoolDashboard('textbooknum', $schoolId, $semester, $year)['time'],
                                    'data'  => $this->schoolDashboard('textbooknum', $schoolId, $semester, $year)['data'],
                                ],
                            ],
                        ],
                    ],
                ],
                'study'                => [
                    'underway'   => [         //進行中
                        'percentage' => $this->schoolStudy('underway', $schoolId, $semester, $year)['percentage'],
                        'id'         => 0,
                        'time'       => $this->schoolStudy('underway', $schoolId, $semester, $year)['time'],
                        'data'       => $this->schoolStudy('underway', $schoolId, $semester, $year)['data'],
                        // $this->underway(),
                    ],
                    'unfinished' => [          //未完成
                        'percentage' => $this->schoolStudy('unfinished', $schoolId, $semester, $year)['percentage'],
                        'id'         => 1,
                        'time'       => $this->schoolStudy('unfinished', $schoolId, $semester, $year)['time'],
                        'data'       => $this->schoolStudy('unfinished', $schoolId, $semester, $year)['data'],
                        // $this->unfinished(),
                    ],
                    'achieve'    => [         //已完成
                        'percentage' => $this->schoolStudy('achieve', $schoolId, $semester, $year)['percentage'],
                        'id'         => 2,
                        'time'       => $this->schoolStudy('achieve', $schoolId, $semester, $year)['time'],
                        'data'       => $this->schoolStudy('achieve', $schoolId, $semester, $year)['data'],
                        // $this->achieve(),
                    ],
                ],
                'totalresources'       => 5651,                                                  //总资源占比
                'onlinetestcomplete'   => $production,                        //線上測驗完成率
                'productionpercentage' => $onlineTest,                        //作業作品完成率
            ];
        } elseif ($schoolId) {
            $data = DB::table('ApiDistricts')->selectRaw('
                   sum(teachnum)                 teachnum,
                   sum(studentnum)               studentnum,
                   sum(patriarchnum)             patriarchnum,
                   sum(curriculum)               curriculum,
                   sum(electronicalnote)         electronicalnote,
                   sum(uploadMovie)              uploadMovie,
                   sum(production)               production,
                   sum(overturnClass)            overturnClass,
                   sum(analogyTest)              analogyTest,
                   sum(onlineTest)               onlineTest,
                   sum(interClassCompetition)    interClassCompetition,
                   sum(HiTeach)                  HiTeach,
                   sum(performanceLogin)         performanceLogin,
                   sum(mergeActivity)            mergeActivity,
                   sum(onlineChecking)           onlineChecking,
                   sum(allLearningProcess)       allLearningProcess,
                   sum(personAge)                personAge,
                   sum(areaShare)                areaShare,
                   sum(schoolShare)              schoolShare,
                   sum(overallResourece)         overallResourece,
                   sum(totalresources)             totalresources,
                   sum(onlineTestComplete)       onlineTestComplete,
                   sum(productionPercentage)     productionPercentage')
                ->where('year', '!=', 0)
                ->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->get();

            /*
             * $schoolInfo
             * 學校名稱
             * 學校編號
             * */
            $schoolInfo = ApiDistricts::query()
                ->join('schoolinfo', 'schoolInfo.SchoolID', '=', 'ApiDistricts.schoolId')
                ->select('schoolInfo.SchoolName', 'schoolInfo.SchoolID', 'schoolInfo.Abbr')
                ->where('ApiDistricts.schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->distinct()
                ->get();

            //個案算平均值
            $percentageData = DB::table('districts_all_schools')->select('productionPercentage', 'onlineTestComplete')
                ->where('year', '!=', 0)
                ->where('onlineTestComplete', '!=', 0)
                ->where('productionPercentage', '!=', 0)
                ->where('schoolID', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->get();

            //判段資料要超過0才會成立
            // if ($percentageData->toArray() != []) {
            if (count($percentageData)) {
                foreach ($percentageData as $item) {
                    $p[] = $item->productionPercentage;
                    $o[] = $item->onlineTestComplete;
                }
                $production = round(array_sum($p) / count($p));
                $onlineTest = round(array_sum($o) / count($o));
            } else {
                $production = 0;
                $onlineTest = 0;
            }

            $JsonData = [
                'schoolname'           => $schoolInfo[0]->SchoolName,                              //學校名稱
                'schoolid'             => $schoolInfo[0]->SchoolID,                                //學校ID，所有為all，學校為數字ID
                'schoolcode'           => $schoolInfo[0]->Abbr,                        //學校簡碼
                'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                'dashboard'            => [
                    'teachlogintable' => [                                          //老師登入
                        'num'      => 1,            //标识符
                        'loginnum' => $this->schoolDashboard('teachlogintable', $schoolId)['num'],   //教师登录数
                        'time'     => $this->schoolDashboard('teachlogintable', $schoolId)['time'],     //折线图X轴时间
                        'data'     => $this->schoolDashboard('teachlogintable', $schoolId)['data'],         //数据
                        // $this->teachLoginTable(),
                    ],
                    'studylogintable' => [         //学生登录情况
                        'num'           => 1,
                        'studyloginnum' => $this->schoolDashboard('studylogintable', $schoolId)['num'],   //学生登录数
                        'time'          => $this->schoolDashboard('studylogintable', $schoolId)['time'],     //柱状图X轴时间
                        'data'          => $this->schoolDashboard('studylogintable', $schoolId)['data'],         //数据
                    ],
                    'smartclasstable' => [
                        'schoolmessage'   => [
                            'id'               => $schoolInfo[0]->schoolId,                       //學校ID
                            'curriculum'       => $data[0]->curriculum,                           //課程總數
                            'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                            'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                            'production'       => $data[0]->production,                           //作業作品數
                            'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                        ],
                        'learningprocess' => [
                            'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                            'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                            'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                            'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                            'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                            'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                            'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                            'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                        ],
                        'chartdata'       => [
                            // $this->chartData(),
                            [
                                'title' => '课程总数',
                                "num"   => 1,
                                "time"  => $this->schoolChartData('curriculum', $schoolId)['time'],
                                "data"  => $this->schoolChartData('curriculum', $schoolId)['data'],
                            ],
                            [
                                'title' => '电子笔记数',
                                "num"   => 2,
                                "time"  => $this->schoolChartData('electronicalnote', $schoolId)['time'],
                                "data"  => $this->schoolChartData('electronicalnote', $schoolId)['data'],
                            ],
                            [
                                'title' => '上传影片数',
                                "num"   => 3,
                                "time"  => $this->schoolChartData('uploadmovie', $schoolId)['time'],
                                "data"  => $this->schoolChartData('uploadmovie', $schoolId)['data'],
                            ],
                            [
                                'title' => '作业作品数',
                                "num"   => 4,
                                "time"  => $this->schoolChartData('production', $schoolId)['time'],
                                "data"  => $this->schoolChartData('production', $schoolId)['data'],
                            ],
                            [
                                'title' => '翻转课堂数',
                                "num"   => 5,
                                "time"  => $this->schoolChartData('overturnClass', $schoolId)['time'],
                                "data"  => $this->schoolChartData('overturnClass', $schoolId)['data'],
                            ],
                            [
                                'title' => '学习历程',
                                "num"   => 6,
                                "time"  => $this->schoolChartData('allLearningProcess', $schoolId)['time'],
                                "data"  => $this->schoolChartData('allLearningProcess', $schoolId)['data'],
                            ]
                            //智慧教堂應用下２個圖表
                        ],
                        'resource'        => [
                            'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                            'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                            'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                            'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                            'subjectnum'       => [                                   //題目數
                                [
                                    'id'    => 0,
                                    'title' => '  题目数',
                                    'num'   => $this->schoolDashboard('subjectnum', $schoolId)['num'],
                                    'time'  => $this->schoolDashboard('subjectnum', $schoolId)['time'],
                                    'data'  => $this->schoolDashboard('subjectnum', $schoolId)['data'],
                                ],
                            ],
                            'examinationnum'   => [                                    //試卷數
                                [
                                    'id'    => 1,
                                    'title' => '试卷数',
                                    'num'   => $this->schoolDashboard('examinationnum', $schoolId)['num'],
                                    'time'  => $this->schoolDashboard('examinationnum', $schoolId)['time'],
                                    'data'  => $this->schoolDashboard('examinationnum', $schoolId)['data'],
                                ],
                            ],
                            'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                [
                                    'id'    => 2,
                                    'title' => '教材数',
                                    'num'   => $this->schoolDashboard('textbooknum', $schoolId)['num'],
                                    'time'  => $this->schoolDashboard('textbooknum', $schoolId)['time'],
                                    'data'  => $this->schoolDashboard('textbooknum', $schoolId)['data'],
                                ],
                            ],
                        ],
                    ],
                ],
                'study'                => [
                    'underway'   => [         //進行中
                        'percentage' => $this->schoolStudy('underway', $schoolId)['percentage'],
                        'id'         => 0,
                        'title'      => '進行中',
                        'time'       => $this->schoolStudy('underway', $schoolId)['time'],
                        'data'       => $this->schoolStudy('underway', $schoolId)['data'],
                        // $this->underway(),
                    ],
                    'unfinished' => [          //未完成
                        'percentage' => $this->schoolStudy('unfinished', $schoolId)['percentage'],
                        'id'         => 1,
                        'title'      => '未完成',
                        'time'       => $this->schoolStudy('unfinished', $schoolId)['time'],
                        'data'       => $this->schoolStudy('unfinished', $schoolId)['data'],
                        // $this->unfinished(),
                    ],
                    'achieve'    => [         //已完成
                        'percentage' => $this->schoolStudy('achieve', $schoolId)['percentage'],
                        'id'         => 2,
                        'title'      => '已完成',
                        'time'       => $this->schoolStudy('achieve', $schoolId)['time'],
                        'data'       => $this->schoolStudy('achieve', $schoolId)['data'],
                        // $this->achieve(),
                    ],

                ],
                'totalresources'       => 5651,                                                  //总资源占比
                'onlinetestcomplete'   => $production,                        //線上測驗完成率
                'productionpercentage' => $onlineTest,                        //作業作品完成率
            ];

        } else {
            return 'Data does not exist';
        }


        return $JsonData;

    }


    /**
     * 全區學校 不用選學期
     * $district_id 學區代碼必需傳入
     */
    public function all(Request $request)
    {
        //接收區代碼
        // dd($this->encodeHashId(1));
        $district_id = $this->decodeHashId($request->d);
        $semester    = $request->semester;
        $year        = $request->year;


        //判斷學區代號存不存在District
        try {
            $district = District_schools::query()->select('district_id')->where('district_id', $district_id)->value('district_id');
        } catch (\Exception $e) {

        }

        if (isset($semester) && isset($year) && isset($district)) {

            $data = DB::table('districts_all_schools')->selectRaw('
                    sum(teachnum)                 teachnum,
                    sum(studentnum)               studentnum,
                    sum(patriarchnum)             patriarchnum,
                    sum(curriculum)               curriculum,
                    sum(electronicalnote)         electronicalnote,
                    sum(uploadMovie)              uploadMovie,
                    sum(production)               production,
                    sum(overturnClass)            overturnClass,
                    sum(analogyTest)              analogyTest,
                    sum(onlineTest)               onlineTest,
                    sum(interClassCompetition)    interClassCompetition,
                    sum(HiTeach)                  HiTeach,
                    sum(performanceLogin)         performanceLogin,
                    sum(mergeActivity)            mergeActivity,
                    sum(onlineChecking)           onlineChecking,
                    sum(allLearningProcess)       allLearningProcess,
                    sum(personAge)                personAge,
                    sum(areaShare)                areaShare,
                    sum(schoolShare)              schoolShare,
                    sum(overallResourece)         overallResourece,
                    sum(totalresources)           totalresources,
                    sum(onlineTestComplete)       onlineTestComplete,
                    sum(productionPercentage)     productionPercentage')
                ->where('year', '!=', 0)
                ->where('semester', '=', $semester)
                ->where('year', '=', $year)
                ->where('districtID', $district)
                ->get();

            //個案算平均值
            $percentageData = DB::table('districts_all_schools')->select('productionPercentage', 'onlineTestComplete')
                ->where('year', '!=', 0)
                ->where('onlineTestComplete', '!=', 0)
                ->where('productionPercentage', '!=', 0)
                ->where('semester', '=', $semester)
                ->where('year', '=', $year)
                ->where('districtID', $district)
                ->get();

            if (!empty($percentageData->toArray())) {
                foreach ($percentageData as $item) {
                    $p[] = $item->productionPercentage;
                    $o[] = $item->onlineTestComplete;
                }
                $production = round(array_sum($p) / count($p));
                $onlineTest = round(array_sum($o) / count($o));
            } else {
                $production = 0;
                $onlineTest = 0;
            }

            $JsonData   = $this->school($district, $semester, $year);
            $JsonData[] = [
                'schoolname'           => '所有学校',                              //學校名稱
                'schoolid'             => 'all',                                //學校ID，所有為all，學校為數字ID
                'schoolcode'           => 'all',                                   //學校檢碼
                'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                'dashboard'            => [
                    'teachlogintable' => [                                          //老師登入
                        'num'      => 1,            //标识符
                        'loginnum' => $this->dashboard('teachlogintable', $district, $semester, $year)['num'],   //教师登录数
                        'time'     => $this->dashboard('teachlogintable', $district, $semester, $year)['time'],     //折线图X轴时间
                        'data'     => $this->dashboard('teachlogintable', $district, $semester, $year)['data'],         //数据
                        // $this->teachLoginTable(),
                    ],
                    'studylogintable' => [         //学生登录情况
                        'num'           => 1,
                        'studyloginnum' => $this->dashboard('studylogintable', $district, $semester, $year)['num'],   //学生登录数
                        'time'          => $this->dashboard('studylogintable', $district, $semester, $year)['time'],     //柱状图X轴时间
                        'data'          => $this->dashboard('studylogintable', $district, $semester, $year)['data'],         //数据
                    ],
                    'smartclasstable' => [
                        'schoolmessage'   => [
                            'id'               => 'all',
                            'curriculum'       => $data[0]->curriculum,                           //課程總數
                            'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                            'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                            'production'       => $data[0]->production,                           //作業作品數
                            'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                        ],
                        'learningprocess' => [
                            'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                            'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                            'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                            'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                            'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                            'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                            'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                            'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                        ],
                        'chartdata'       => [
                            [
                                'title' => '课程总数',
                                "num"   => 1,
                                "time"  => $this->chartData('curriculum', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('curriculum', $district, $semester, $year)['data'],
                            ],
                            [
                                'title' => '电子笔记数',
                                "num"   => 2,
                                "time"  => $this->chartData('electronicalnote', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('electronicalnote', $district, $semester, $year)['data'],
                            ],
                            [
                                'title' => '上传影片数',
                                "num"   => 3,
                                "time"  => $this->chartData('uploadmovie', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('uploadmovie', $district, $semester, $year)['data'],
                            ],
                            [
                                'title' => '作业作品数',
                                "num"   => 4,
                                "time"  => $this->chartData('production', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('production', $district, $semester, $year)['data'],
                            ],
                            [
                                'title' => '翻转课堂数',
                                "num"   => 5,
                                "time"  => $this->chartData('overturnClass', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('overturnClass', $district, $semester, $year)['data'],
                            ],
                            [
                                'title' => '学习历程',
                                "num"   => 6,
                                "time"  => $this->chartData('allLearningProcess', $district, $semester, $year)['time'],
                                "data"  => $this->chartData('allLearningProcess', $district, $semester, $year)['data'],
                            ]
                            //智慧教堂應用下２個圖表
                        ],
                        'resource'        => [
                            'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                            'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                            'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                            'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                            'subjectnum'       => [                                   //題目數
                                [
                                    'id'    => 0,
                                    'title' => '  题目数',
                                    'num'   => $this->dashboard('subjectnum', $district, $semester, $year)['num'],
                                    'time'  => $this->dashboard('subjectnum', $district, $semester, $year)['time'],
                                    'data'  => $this->dashboard('subjectnum', $district, $semester, $year)['data'],
                                ],
                            ],
                            'examinationnum'   => [                                    //試卷數
                                [
                                    'id'    => 1,
                                    'title' => '试卷数',
                                    'num'   => $this->dashboard('examinationnum', $district, $semester, $year)['num'],
                                    'time'  => $this->dashboard('examinationnum', $district, $semester, $year)['time'],
                                    'data'  => $this->dashboard('examinationnum', $district, $semester, $year)['data'],
                                ],
                            ],
                            'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                [
                                    'id'    => 2,
                                    'title' => '教材数',
                                    'num'   => $this->dashboard('textbooknum', $district, $semester, $year)['num'],
                                    'time'  => $this->dashboard('textbooknum', $district, $semester, $year)['time'],
                                    'data'  => $this->dashboard('textbooknum', $district, $semester, $year)['data'],
                                ],
                            ],
                        ],
                    ],
                ],
                'study'                => [
                    'underway'   => [         //進行中
                        'percentage' => $this->study('underway', $district, $semester, $year)['percentage'],
                        'id'         => 0,
                        'title'      => '進行中',
                        'time'       => $this->study('underway', $district, $semester, $year)['time'],
                        'data'       => $this->study('underway', $district, $semester, $year)['data'],
                    ],
                    'unfinished' => [          //未完成
                        'percentage' => $this->study('unfinished', $district, $semester, $year)['percentage'],
                        'id'         => 1,
                        'title'      => '未完成',
                        'time'       => $this->study('unfinished', $district, $semester, $year)['time'],
                        'data'       => $this->study('unfinished', $district, $semester, $year)['data'],
                    ],
                    'achieve'    => [         //已完成
                        'percentage' => $this->study('achieve', $district, $semester, $year)['percentage'],
                        'id'         => 2,
                        'title'      => '已完成',
                        'time'       => $this->study('achieve', $district, $semester, $year)['time'],
                        'data'       => $this->study('achieve', $district, $semester, $year)['data'],
                    ],

                ],
                'totalresources'       => 5651,                                                  //总资源占比
                'onlinetestcomplete'   => $production,                          //線上測驗完成率
                'productionpercentage' => $onlineTest,                        //作業作品完成率

            ];

            return response()->json(array_reverse($JsonData));
        } elseif (isset($district)) {
            $data = DB::table('districts_all_schools')->selectRaw('
                    sum(teachnum)                 teachnum,
                    sum(studentnum)               studentnum,
                    sum(patriarchnum)             patriarchnum,
                    sum(curriculum)               curriculum,
                    sum(electronicalnote)         electronicalnote,
                    sum(uploadMovie)              uploadMovie,
                    sum(production)               production,
                    sum(overturnClass)            overturnClass,
                    sum(analogyTest)              analogyTest,
                    sum(onlineTest)               onlineTest,
                    sum(interClassCompetition)    interClassCompetition,
                    sum(HiTeach)                  HiTeach,
                    sum(performanceLogin)         performanceLogin,
                    sum(mergeActivity)            mergeActivity,
                    sum(onlineChecking)           onlineChecking,
                    sum(allLearningProcess)       allLearningProcess,
                    sum(personAge)                personAge,
                    sum(areaShare)                areaShare,
                    sum(schoolShare)              schoolShare,
                    sum(overallResourece)         overallResourece,
                    sum(totalresources)             totalresources,
                    sum(onlineTestComplete)       onlineTestComplete,
                    sum(productionPercentage)     productionPercentage')
                ->where('year', '!=', 0)
                ->where('districtID', $district_id)
                ->get();

            //個案算平均值
            $percentageData = DB::table('districts_all_schools')->select('productionPercentage', 'onlineTestComplete')
                ->where('year', '!=', 0)
                ->where('onlineTestComplete', '!=', 0)
                ->where('productionPercentage', '!=', 0)
                ->where('districtID', $district_id)
                ->get();

            if (!empty($percentageData->toArray())) {
                foreach ($percentageData as $item) {
                    $p[] = $item->productionPercentage;
                    $o[] = $item->onlineTestComplete;
                }
                $production = round(array_sum($p) / count($p));
                $onlineTest = round(array_sum($o) / count($o));
            } else {
                $production = 0;
                $onlineTest = 0;
            }

            $JsonData   = $this->school($district_id);
            $JsonData[] = [
                'schoolname'           => '所有学校',                              //學校名稱
                'schoolid'             => 'all',                                //學校ID，所有為all，學校為數字ID
                'schoolcode'           => 'all',                                  //學校簡碼
                'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                'dashboard'            => [
                    'teachlogintable' => [                                          //老師登入
                        'num'      => 1,            //标识符
                        'loginnum' => $this->dashboard('teachlogintable', $district_id)['num'],   //教师登录数
                        'time'     => $this->dashboard('teachlogintable', $district_id)['time'],     //折线图X轴时间
                        'data'     => $this->dashboard('teachlogintable', $district_id)['data'],         //数据
                        // $this->teachLoginTable(),
                    ],
                    'studylogintable' => [         //学生登录情况
                        'num'           => 1,
                        'studyloginnum' => $this->dashboard('studylogintable', $district_id)['num'],  //学生登录数
                        'time'          => $this->dashboard('studylogintable', $district_id)['time'], //柱状图X轴时间
                        'data'          => $this->dashboard('studylogintable', $district_id)['data'],      //数据
                    ],
                    'smartclasstable' => [
                        'schoolmessage'   => [
                            'id'               => 'all',                                          //學校代碼
                            'curriculum'       => $data[0]->curriculum,                           //課程總數
                            'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                            'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                            'production'       => $data[0]->production,                           //作業作品數
                            'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                        ],
                        'learningprocess' => [
                            'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                            'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                            'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                            'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                            'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                            'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                            'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                            'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                        ],
                        'chartdata'       => [
                            [
                                'title' => '课程总数',
                                "num"   => 1,
                                "time"  => $this->chartData('curriculum', $district_id)['time'],
                                "data"  => $this->chartData('curriculum', $district_id)['data'],
                            ],
                            [
                                'title' => '电子笔记数',
                                "num"   => 2,
                                "time"  => $this->chartData('electronicalnote', $district_id)['time'],
                                "data"  => $this->chartData('electronicalnote', $district_id)['data'],
                            ],
                            [
                                'title' => '上传影片数',
                                "num"   => 3,
                                "time"  => $this->chartData('uploadmovie', $district_id)['time'],
                                "data"  => $this->chartData('uploadmovie', $district_id)['data'],
                            ],
                            [
                                'title' => '作业作品数',
                                "num"   => 4,
                                "time"  => $this->chartData('production', $district_id)['time'],
                                "data"  => $this->chartData('production', $district_id)['data'],
                            ],
                            [
                                'title' => '翻转课堂数',
                                "num"   => 5,
                                "time"  => $this->chartData('overturnClass', $district_id)['time'],
                                "data"  => $this->chartData('overturnClass', $district_id)['data'],
                            ],
                            [
                                'title' => '学习历程',
                                "num"   => 6,
                                "time"  => $this->chartData('allLearningProcess', $district_id)['time'],
                                "data"  => $this->chartData('allLearningProcess', $district_id)['data'],
                            ]
                            //智慧教堂應用下２個圖表
                        ],
                        'resource'        => [
                            'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                            'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                            'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                            'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                            'subjectnum'       => [                                   //題目數
                                [
                                    'id'    => 0,
                                    'title' => '  题目数',
                                    'num'   => $this->dashboard('subjectnum', $district_id)['num'],
                                    'time'  => $this->dashboard('subjectnum', $district_id)['time'],
                                    'data'  => $this->dashboard('subjectnum', $district_id)['data'],
                                ],
                            ],
                            'examinationnum'   => [                                    //試卷數
                                [
                                    'id'    => 1,
                                    'title' => '试卷数',
                                    'num'   => $this->dashboard('examinationnum', $district_id)['num'],
                                    'time'  => $this->dashboard('examinationnum', $district_id)['time'],
                                    'data'  => $this->dashboard('examinationnum', $district_id)['data'],
                                ],
                            ],
                            'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                [
                                    'id'    => 2,
                                    'title' => '教材数',
                                    'num'   => $this->dashboard('textbooknum', $district_id)['num'],
                                    'time'  => $this->dashboard('textbooknum', $district_id)['time'],
                                    'data'  => $this->dashboard('textbooknum', $district_id)['data'],
                                ],
                            ],
                        ],
                    ],
                ],
                'study'                => [
                    'underway'   => [         //進行中
                        'percentage' => $this->study('underway', $district_id)['percentage'],
                        'id'         => 0,
                        'title'      => '進行中',
                        'time'       => $this->study('underway', $district_id)['time'],
                        'data'       => $this->study('underway', $district_id)['data'],
                        // $this->underway(),
                    ],
                    'unfinished' => [          //未完成
                        'percentage' => $this->study('unfinished', $district_id)['percentage'],
                        'id'         => 1,
                        'title'      => '未完成',
                        'time'       => $this->study('unfinished', $district_id)['time'],
                        'data'       => $this->study('unfinished', $district_id)['data'],
                        // $this->unfinished(),
                    ],
                    'achieve'    => [         //已完成
                        'percentage' => $this->study('achieve', $district_id)['percentage'],
                        'id'         => 2,
                        'title'      => '已完成',
                        'time'       => $this->study('achieve', $district_id)['time'],
                        'data'       => $this->study('achieve', $district_id)['data'],
                        // $this->achieve(),
                    ],

                ],
                'totalresources'       => 5651,                                 //总资源占比
                'onlinetestcomplete'   => $production,                          //線上測驗完成率
                'productionpercentage' => $onlineTest,                        //作業作品完成率

            ];


            return response()->json(array_reverse($JsonData));
        } else {
            return response()->json('Data does not exist', '400');
        }
    }

    /**
     * 共用function
     * 適用對象 學區格式單一學校顯示 all
     * 顯示學區單一學校
     * 需傳入學區代號 $district_id
     */
    public function school($district_id = null, $semester = null, $year = null)
    {


        $schools = ApiDistricts::query()
            ->join('district_schools', 'district_schools.SchoolID', '=', 'ApiDistricts.schoolId')
            ->select('ApiDistricts.schoolId')
            ->where('district_schools.district_id', $district_id)
            ->orderBy('schoolId', 'asc')
            ->distinct()
            ->get();

        if (isset($semester) && isset($year) && isset($district_id)) {
            foreach ($schools as $school) {

                $data = DB::table('districts_all_schools')->selectRaw('
                   sum(teachnum)                 teachnum,
                   sum(studentnum)               studentnum,
                   sum(patriarchnum)             patriarchnum,
                   sum(curriculum)               curriculum,
                   sum(electronicalnote)         electronicalnote,
                   sum(uploadMovie)              uploadMovie,
                   sum(production)               production,
                   sum(overturnClass)            overturnClass,
                   sum(analogyTest)              analogyTest,
                   sum(onlineTest)               onlineTest,
                   sum(interClassCompetition)    interClassCompetition,
                   sum(HiTeach)                  HiTeach,
                   sum(performanceLogin)         performanceLogin,
                   sum(mergeActivity)            mergeActivity,
                   sum(onlineChecking)           onlineChecking,
                   sum(allLearningProcess)       allLearningProcess,
                   sum(personAge)                personAge,
                   sum(areaShare)                areaShare,
                   sum(schoolShare)              schoolShare,
                   sum(overallResourece)         overallResourece,
                   sum(totalresources)             totalresources,
                   sum(onlineTestComplete)       onlineTestComplete,
                   sum(productionPercentage)     productionPercentage')
                    ->where('year', '!=', 0)
                    ->where('districtID', $district_id)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->where('schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->get();

                /*
                 * $schoolInfo
                 * 學校名稱
                 * 學校編號
                 * */
                $schoolInfo = DistrictsAllSchool::query()
                    ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'districts_all_schools.schoolId')
                    ->select('schoolinfo.SchoolName', 'schoolinfo.SchoolID', 'schoolinfo.Abbr')
                    ->where('districtID', $district_id)
                    ->where('semester', $semester)
                    ->where('districts_all_schools.schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->distinct()
                    ->get();


                //個案算平均值
                $percentageData = DB::table('districts_all_schools')->select('productionPercentage', 'onlineTestComplete')
                    ->where('year', '!=', 0)
                    ->where('onlineTestComplete', '!=', 0)
                    ->where('productionPercentage', '!=', 0)
                    ->where('districtID', $district_id)
                    ->where('districtID', $district_id)
                    ->where('semester', $semester)
                    ->where('schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->get();

                if (!empty($percentageData->toArray())) {
                    foreach ($percentageData as $item) {
                        $p[] = $item->productionPercentage;
                        $o[] = $item->onlineTestComplete;
                    }
                    $production = round(array_sum($p) / count($p));
                    $onlineTest = round(array_sum($o) / count($o));
                } else {
                    $production = 0;
                    $onlineTest = 0;
                }

                $JsonData[] = [
                    'schoolname'           => $schoolInfo[0]->SchoolName,                              //學校名稱
                    'schoolid'             => $schoolInfo[0]->SchoolID,                                //學校ID，所有為all，學校為數字ID
                    'schoolcode'           => $schoolInfo[0]->Abbr,                        //學校簡碼
                    'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                    'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                    'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                    'dashboard'            => [
                        'teachlogintable' => [                                          //老師登入
                            'num'      => 1,            //标识符
                            'loginnum' => $this->schoolDashboard('teachlogintable', $school->schoolId, $semester, $year)['num'],   //教师登录数
                            'time'     => $this->schoolDashboard('teachlogintable', $school->schoolId, $semester, $year)['time'],     //折线图X轴时间
                            'data'     => $this->schoolDashboard('teachlogintable', $school->schoolId, $semester, $year)['data'],         //数据
                            // $this->teachLoginTable(),
                        ],
                        'studylogintable' => [         //学生登录情况
                            'num'           => 1,
                            'studyloginnum' => $this->schoolDashboard('studylogintable', $school->schoolId, $semester, $year)['num'], //学生登录数
                            'time'          => $this->schoolDashboard('studylogintable', $school->schoolId, $semester, $year)['time'],//柱状图X轴时间
                            'data'          => $this->schoolDashboard('studylogintable', $school->schoolId, $semester, $year)['data'],//数据
                        ],
                        'smartclasstable' => [
                            'schoolmessage'   => [
                                'id'               => $schoolInfo[0]->SchoolID,                       //學校ＩＤ
                                'curriculum'       => $data[0]->curriculum,                           //課程總數
                                'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                                'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                                'production'       => $data[0]->production,                           //作業作品數
                                'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                            ],
                            'learningprocess' => [
                                'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                                'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                                'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                                'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                                'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                                'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                                'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                                'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                            ],
                            'chartdata'       => [
                                // $this->chartData(),
                                [
                                    'title' => '课程总数',
                                    "num"   => 1,
                                    "time"  => $this->schoolChartData('curriculum', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('curriculum', $school->schoolId, $semester, $year)['data'],
                                ],
                                [
                                    'title' => '电子笔记数',
                                    "num"   => 2,
                                    "time"  => $this->schoolChartData('electronicalnote', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('electronicalnote', $school->schoolId, $semester, $year)['data'],
                                ],
                                [
                                    'title' => '上传影片数',
                                    "num"   => 3,
                                    "time"  => $this->schoolChartData('uploadmovie', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('uploadmovie', $school->schoolId, $semester, $year)['data'],
                                ],
                                [
                                    'title' => '作业作品数',
                                    "num"   => 4,
                                    "time"  => $this->schoolChartData('production', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('production', $school->schoolId, $semester, $year)['data'],
                                ],
                                [
                                    'title' => '翻转课堂数',
                                    "num"   => 5,
                                    "time"  => $this->schoolChartData('overturnClass', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('overturnClass', $school->schoolId, $semester, $year)['data'],
                                ],
                                [
                                    'title' => '学习历程',
                                    "num"   => 6,
                                    "time"  => $this->schoolChartData('allLearningProcess', $school->schoolId, $semester, $year)['time'],
                                    "data"  => $this->schoolChartData('allLearningProcess', $school->schoolId, $semester, $year)['data'],
                                ]
                                //智慧教堂應用下２個圖表
                            ],
                            'resource'        => [
                                'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                                'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                                'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                                'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                                'subjectnum'       => [                                   //題目數
                                    [
                                        'id'    => 0,
                                        'title' => '  题目数',
                                        'num'   => $this->schoolDashboard('subjectnum', $school->schoolId, $semester, $year)['num'],
                                        'time'  => $this->schoolDashboard('subjectnum', $school->schoolId, $semester, $year)['time'],
                                        'data'  => $this->schoolDashboard('subjectnum', $school->schoolId, $semester, $year)['data'],
                                    ],
                                ],
                                'examinationnum'   => [                                    //試卷數
                                    [
                                        'id'    => 1,
                                        'title' => '试卷数',
                                        'num'   => $this->schoolDashboard('examinationnum', $school->schoolId, $semester, $year)['num'],
                                        'time'  => $this->schoolDashboard('examinationnum', $school->schoolId, $semester, $year)['time'],
                                        'data'  => $this->schoolDashboard('examinationnum', $school->schoolId, $semester, $year)['data'],
                                    ],
                                ],
                                'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                    [
                                        'id'    => 2,
                                        'title' => '教材数',
                                        'num'   => $this->schoolDashboard('textbooknum', $school->schoolId, $semester, $year)['num'],
                                        'time'  => $this->schoolDashboard('textbooknum', $school->schoolId, $semester, $year)['time'],
                                        'data'  => $this->schoolDashboard('textbooknum', $school->schoolId, $semester, $year)['data'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'study'                => [
                        'underway'   => [         //進行中
                            'percentage' => $this->schoolStudy('underway', $school->schoolId, $semester, $year)['percentage'],
                            'id'         => 0,
                            'title'      => '進行中',
                            'time'       => $this->schoolStudy('underway', $school->schoolId, $semester, $year)['time'],
                            'data'       => $this->schoolStudy('underway', $school->schoolId, $semester, $year)['data'],
                            // $this->underway(),
                        ],
                        'unfinished' => [          //未完成
                            'percentage' => $this->schoolStudy('unfinished', $school->schoolId, $semester, $year)['percentage'],
                            'id'         => 1,
                            'title'      => '未完成',
                            'time'       => $this->schoolStudy('unfinished', $school->schoolId, $semester, $year)['time'],
                            'data'       => $this->schoolStudy('unfinished', $school->schoolId, $semester, $year)['data'],
                            // $this->unfinished(),
                        ],
                        'achieve'    => [         //已完成
                            'percentage' => $this->schoolStudy('achieve', $school->schoolId, $semester, $year)['percentage'],
                            'id'         => 2,
                            'title'      => '已完成',
                            'time'       => $this->schoolStudy('achieve', $school->schoolId, $semester, $year)['time'],
                            'data'       => $this->schoolStudy('achieve', $school->schoolId, $semester, $year)['data'],
                            // $this->achieve(),
                        ],
                    ],
                    'totalresources'       => 5651,                                                  //总资源占比
                    'onlinetestcomplete'   => $production,                        //線上測驗完成率
                    'productionpercentage' => $onlineTest,                        //作業作品完成率
                ];

            }
        } elseif (isset($district_id)) {
            foreach ($schools as $school) {

                $data = DB::table('districts_all_schools')->selectRaw('
                   sum(teachnum)                 teachnum,
                   sum(studentnum)               studentnum,
                   sum(patriarchnum)             patriarchnum,
                   sum(curriculum)               curriculum,
                   sum(electronicalnote)         electronicalnote,
                   sum(uploadMovie)              uploadMovie,
                   sum(production)               production,
                   sum(overturnClass)            overturnClass,
                   sum(analogyTest)              analogyTest,
                   sum(onlineTest)               onlineTest,
                   sum(interClassCompetition)    interClassCompetition,
                   sum(HiTeach)                  HiTeach,
                   sum(performanceLogin)         performanceLogin,
                   sum(mergeActivity)            mergeActivity,
                   sum(onlineChecking)           onlineChecking,
                   sum(allLearningProcess)       allLearningProcess,
                   sum(personAge)                personAge,
                   sum(areaShare)                areaShare,
                   sum(schoolShare)              schoolShare,
                   sum(overallResourece)         overallResourece,
                   sum(totalresources)             totalresources,
                   sum(onlineTestComplete)       onlineTestComplete,
                   sum(productionPercentage)     productionPercentage')
                    ->where('year', '!=', 0)
                    ->where('districtID', $district_id)
                    ->where('schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->get();

                /*
                 * $schoolInfo
                 * 學校名稱
                 * 學校編號
                 * */
                $schoolInfo = DistrictsAllSchool::query()
                    ->join('schoolinfo', 'schoolinfo.SchoolID', '=', 'districts_all_schools.schoolId')
                    ->select('schoolinfo.SchoolName', 'schoolinfo.SchoolID', 'schoolinfo.Abbr')
                    ->where('districtID', $district_id)
                    ->where('districts_all_schools.schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->distinct()
                    ->get();


                //個案算平均值
                $percentageData = DB::table('districts_all_schools')->select('productionPercentage', 'onlineTestComplete')
                    ->where('year', '!=', 0)
                    ->where('onlineTestComplete', '!=', 0)
                    ->where('productionPercentage', '!=', 0)
                    ->where('districtID', $district_id)
                    ->where('schoolID', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->get();

                if (!empty($percentageData->toArray())) {
                    foreach ($percentageData as $item) {
                        $p[] = $item->productionPercentage;
                        $o[] = $item->onlineTestComplete;
                    }
                    $production = round(array_sum($p) / count($p));
                    $onlineTest = round(array_sum($o) / count($o));
                } else {
                    $production = 0;
                    $onlineTest = 0;
                }

                $JsonData[] = [
                    'schoolname'           => $schoolInfo[0]->SchoolName,                              //學校名稱
                    'schoolid'             => $schoolInfo[0]->SchoolID,                                //學校ID，所有為all，學校為數字ID
                    'schoolcode'           => $schoolInfo[0]->Abbr,                        //學校簡碼
                    'teachnum'             => $data[0]->teachnum,                                //老師帳號啟用數量
                    'studentnum'           => $data[0]->studentnum,                              //學生帳號啟用數量
                    'patriarchnum'         => $data[0]->patriarchnum,                            //家長帳號啟用數量
                    'dashboard'            => [
                        'teachlogintable' => [                                          //老師登入
                            'num'      => 1,            //标识符
                            'loginnum' => $this->schoolDashboard('teachlogintable', $school->schoolId)['num'],   //教师登录数
                            'time'     => $this->schoolDashboard('teachlogintable', $school->schoolId)['time'],     //折线图X轴时间
                            'data'     => $this->schoolDashboard('teachlogintable', $school->schoolId)['data'],         //数据
                            // $this->teachLoginTable(),
                        ],
                        'studylogintable' => [         //学生登录情况
                            'num'           => 1,
                            'studyloginnum' => $this->schoolDashboard('studylogintable', $school->schoolId)['num'],   //学生登录数
                            'time'          => $this->schoolDashboard('studylogintable', $school->schoolId)['time'],     //柱状图X轴时间
                            'data'          => $this->schoolDashboard('studylogintable', $school->schoolId)['data'],         //数据
                        ],
                        'smartclasstable' => [
                            'schoolmessage'   => [
                                'id'               => $schoolInfo[0]->SchoolID,                       //學校ＩＤ
                                'curriculum'       => $data[0]->curriculum,                           //課程總數
                                'electronicalnote' => $data[0]->electronicalnote,                     //電子筆記
                                'uploadmovie'      => $data[0]->uploadMovie,                          //上傳影片
                                'production'       => $data[0]->production,                           //作業作品數
                                'overturnclass'    => $data[0]->overturnClass,                        //翻轉課堂
                            ],
                            'learningprocess' => [
                                'analogytest'           => $data[0]->analogyTest,                //模擬測驗
                                'onlinetest'            => $data[0]->onlineTest,                 //線上測驗
                                'interclasscompetition' => $data[0]->interClassCompetition,      //班級競賽
                                'HiTeach'               => $data[0]->HiTeach,                    //HiTeach
                                'performancelogin'      => $data[0]->performanceLogin,           //成績登入
                                'mergeactivity'         => $data[0]->mergeActivity,              //合並活動
                                'onlinechecking'        => $data[0]->onlineChecking,             //網路閱卷
                                'alllearningprocess'    => $data[0]->allLearningProcess,         //學習歷程總數
                            ],
                            'chartdata'       => [
                                // $this->chartData(),
                                [
                                    'title' => '课程总数',
                                    "num"   => 1,
                                    "time"  => $this->schoolChartData('curriculum', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('curriculum', $school->schoolId)['data'],
                                ],
                                [
                                    'title' => '电子笔记数',
                                    "num"   => 2,
                                    "time"  => $this->schoolChartData('electronicalnote', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('electronicalnote', $school->schoolId)['data'],
                                ],
                                [
                                    'title' => '上传影片数',
                                    "num"   => 3,
                                    "time"  => $this->schoolChartData('uploadmovie', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('uploadmovie', $school->schoolId)['data'],
                                ],
                                [
                                    'title' => '作业作品数',
                                    "num"   => 4,
                                    "time"  => $this->schoolChartData('production', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('production', $school->schoolId)['data'],
                                ],
                                [
                                    'title' => '翻转课堂数',
                                    "num"   => 5,
                                    "time"  => $this->schoolChartData('overturnClass', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('overturnClass', $school->schoolId)['data'],
                                ],
                                [
                                    'title' => '学习历程',
                                    "num"   => 6,
                                    "time"  => $this->schoolChartData('allLearningProcess', $school->schoolId)['time'],
                                    "data"  => $this->schoolChartData('allLearningProcess', $school->schoolId)['data'],
                                ]
                                //智慧教堂應用下２個圖表
                            ],
                            'resource'        => [
                                'personage'        => ($data[0]->personAge == null) ? '0' : $data[0]->personAge,                       //个人所属
                                'areashare'        => ($data[0]->areaShare == null) ? '0' : $data[0]->areaShare,                       //区级分享
                                'schoolshare'      => ($data[0]->schoolShare == null) ? '0' : $data[0]->schoolShare,                   //校级分享
                                'overallresourece' => ($data[0]->overallResourece == null) ? '0' : $data[0]->overallResourece,         //总资源分享数
                                'subjectnum'       => [                                   //題目數
                                    [
                                        'id'    => 0,
                                        'title' => '  题目数',
                                        'num'   => $this->schoolDashboard('subjectnum', $school->schoolId)['num'],
                                        'time'  => $this->schoolDashboard('subjectnum', $school->schoolId)['time'],
                                        'data'  => $this->schoolDashboard('subjectnum', $school->schoolId)['data'],
                                    ],
                                ],
                                'examinationnum'   => [                                    //試卷數
                                    [
                                        'id'    => 1,
                                        'title' => '试卷数',
                                        'num'   => $this->schoolDashboard('examinationnum', $school->schoolId)['num'],
                                        'time'  => $this->schoolDashboard('examinationnum', $school->schoolId)['time'],
                                        'data'  => $this->schoolDashboard('examinationnum', $school->schoolId)['data'],
                                    ],
                                ],
                                'textbooknum'      => [                      //教材数 { '2012': 457, '2013': 865, 目前沒有教材數
                                    [
                                        'id'    => 2,
                                        'title' => '教材数',
                                        'num'   => $this->schoolDashboard('textbooknum', $school->schoolId)['num'],
                                        'time'  => $this->schoolDashboard('textbooknum', $school->schoolId)['time'],
                                        'data'  => $this->schoolDashboard('textbooknum', $school->schoolId)['data'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'study'                => [
                        'underway'   => [         //進行中
                            'percentage' => $this->schoolStudy('underway', $school->schoolId)['percentage'],
                            'id'         => 0,
                            'title'      => '進行中',
                            'time'       => $this->schoolStudy('underway', $school->schoolId)['time'],
                            'data'       => $this->schoolStudy('underway', $school->schoolId)['data'],
                            // $this->underway(),
                        ],
                        'unfinished' => [          //未完成
                            'percentage' => $this->schoolStudy('unfinished', $school->schoolId)['percentage'],
                            'id'         => 1,
                            'title'      => '未完成',
                            'time'       => $this->schoolStudy('unfinished', $school->schoolId)['time'],
                            'data'       => $this->schoolStudy('unfinished', $school->schoolId)['data'],
                            // $this->unfinished(),
                        ],
                        'achieve'    => [         //已完成
                            'percentage' => $this->schoolStudy('achieve', $school->schoolId)['percentage'],
                            'id'         => 2,
                            'title'      => '已完成',
                            'time'       => $this->schoolStudy('achieve', $school->schoolId)['time'],
                            'data'       => $this->schoolStudy('achieve', $school->schoolId)['data'],
                            // $this->achieve(),
                        ],
                    ],
                    'totalresources'       => 5651,                                                  //总资源占比
                    'onlinetestcomplete'   => $production,                        //線上測驗完成率
                    'productionpercentage' => $onlineTest,                        //作業作品完成率
                ];
            }
        } else {
            $JsonData = [];
        }
        return $JsonData;
    }

    /**
     * 共用function
     * 適用對象 all
     * 適用參數 subjectNum examinationNum textBookNum studyLoginTable
     * $ApiData='' 參數使用僅限適用對象
     * $districts 判斷學校所屬哪一區
     * 學區統計
     * 單一學校無法使用
     */
    public function dashboard($ApiData = null, $district_id = null, $semester = null, $year = null)
    {
        //學校不重複
        $schools = ApiDistricts::query()
            ->join('district_schools', 'district_schools.SchoolID', '=', 'ApiDistricts.schoolId')
            ->select('ApiDistricts.schoolId')
            ->where('district_schools.district_id', $district_id)
            ->orderBy('schoolId', 'asc')
            ->distinct()
            ->get();

        foreach ($schools as $school) {
            //學區
            if (isset($district_id) && isset($semester) && isset($year)) {
                $ApiDistricts = DB::table('ApiDistricts')->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->get();
            } elseif (isset($district_id)) {
                $ApiDistricts = DB::table('ApiDistricts')->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    // ->where('semester', $semester)
                    // ->where('year', $year)
                    ->get();
            } else {
                $ApiDistricts = null;
            }

            // if ($ApiDistricts->toArray() != []) {
            //判段資料要超過0才會成立
            if (count($ApiDistricts)) {
                foreach ($ApiDistricts as $ApiDistrict) {
                    //取json值
                    $yearApiDistricts = json_decode($ApiDistrict->$ApiData, true);

                    if ($yearApiDistricts != 0) {
                        foreach ($yearApiDistricts as $yearApiDistrict) {
                            if (isset($yearData[$yearApiDistrict['year']])) {
                                $yearData[$yearApiDistrict['year']] += $yearApiDistrict['total'];
                            } else {
                                $yearData[$yearApiDistrict['year']] = $yearApiDistrict['total'];
                            }
                        }
                    } else {
                        $yearData = null;
                    }
                }

                if ($yearData != null) {
                    //排除 空值
                    $yearData = array_filter($yearData);
                    //排序
                    ksort($yearData);

                    $data[] = $yearData;

                } else {
                    $data = [];
                }
            } else {
                $data = [];
            }
        }

        $arrayData = $this->array_merge($data);

        $jsonData = [
            'num'  => array_sum($arrayData),
            'time' => array_keys($arrayData),
            'data' => array_values($arrayData),
        ];


        return $jsonData;
    }

    /**
     * 共用function
     * 適用對象 all
     * 適用參數 underway unfinished achieve
     * $ApiData='' 參數使用僅限適用對象
     * $districts 判斷學校所屬哪一區
     * 學區統計
     * 單一學校無法使用
     */
    public function study($ApiData = null, $district_id = null, $semester = null, $year = null)
    {
        $schools = ApiDistricts::query()
            ->join('district_schools', 'district_schools.SchoolID', '=', 'ApiDistricts.schoolId')
            ->select('ApiDistricts.schoolId')
            ->where('district_schools.district_id', $district_id)
            ->orderBy('schoolId', 'asc')
            ->distinct()
            ->get();

        foreach ($schools as $school) {
            //學區
            if (isset($district_id) && isset($semester) && isset($year)) {
                $ApiDistricts = DB::table('ApiDistricts')->select('year', 'underway', 'unfinished', 'achieve')
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->get();
            } elseif (isset($district_id)) {
                $ApiDistricts = DB::table('ApiDistricts')->select('year', 'underway', 'unfinished', 'achieve')
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    // ->where('semester', $semester)
                    // ->where('year', $year)
                    ->get();
            } else {
                $ApiDistricts = null;
            }

            // if ($ApiDistricts->toArray() != []) {
            //判段資料要超過0才會成立
            if (count($ApiDistricts)) {
                foreach ($ApiDistricts as $ApiDistrict) {
                    //取json值
                    $yearApiDistricts = json_decode($ApiDistrict->$ApiData, true);
                    if ($yearApiDistricts != null) {
                        foreach ($yearApiDistricts as $yearApiDistrict) {

                            if (isset($yearData[$yearApiDistrict['year']])) {
                                $yearData[$yearApiDistrict['year']] += $yearApiDistrict['total'];
                            } else {
                                $yearData[$yearApiDistrict['year']] = $yearApiDistrict['total'];
                            }
                        }
                    } else {
                        $yearData = null;
                    }

                }
                if ($yearData != null) {
                    //排除 空值
                    $yearData = array_filter($yearData);
                    //排序
                    ksort($yearData);
                    //存全部學校array
                    $data[] = $yearData;
                } else {
                    $data = [];
                }
            } else {
                $data = [];
            }
        }
        //合併多筆學校array
        $arrayData = $this->array_merge($data);

        $jsonData = [
            'percentage' => array_sum($arrayData) / 1000,
            'time'       => array_keys($arrayData),
            'data'       => array_values($arrayData),
        ];


        return $jsonData;
    }

    /**
     * 共用function
     * 適用對象 all
     * 適用參數 subjectNum examinationNum textBookNum overturnClass allLearningProcess
     * $ApiData='' 參數僅限適用參數
     * $districts 判斷學校所屬哪一區
     * 學區統計
     * 單一學校無法使用
     */
    public function chartData($ApiData = null, $district_id = null, $semester = null, $year = null)
    {
        //學校不重複
        $schools = ApiDistricts::query()
            ->join('district_schools', 'district_schools.SchoolID', '=', 'ApiDistricts.schoolId')
            ->select('ApiDistricts.schoolId')
            ->where('district_schools.district_id', $district_id)
            ->orderBy('schoolId', 'asc')
            ->distinct()
            ->get();

        foreach ($schools as $school) {

            if (isset($district_id) && isset($semester) && $year) {
                //學區
                $ApiDistricts = DB::table('ApiDistricts')->selectRaw("sum($ApiData) as total,year")
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    ->where('semester', $semester)
                    ->where('year', $year)
                    ->groupBy('year')
                    ->get();
            } elseif (isset($district_id)) {
                //學區
                $ApiDistricts = DB::table('ApiDistricts')->selectRaw("sum($ApiData) as total,year")
                    ->where('schoolId', '=', ($school->schoolId == 0) ? 0 : $school->schoolId)
                    ->where('year', '!=', 0)
                    ->groupBy('year')
                    ->get();
            } else {
                $ApiDistricts = null;
            }

            // if ($ApiDistricts != null) {
            //判段資料要超過0才會成立
            if (count($ApiDistricts)) {
                foreach ($ApiDistricts as $apiDistrict) {

                    if (isset($yearData[$apiDistrict->year])) {
                        $yearData[$apiDistrict->year] += $apiDistrict->total;
                    } else {
                        $yearData[$apiDistrict->year] = $apiDistrict->total;
                    }
                }
                if ($yearData != null) {

                    //排除 空值
                    $yearData = array_filter($yearData);

                    //排序
                    ksort($yearData);
                    //存所有學校資訊
                    $data[] = $yearData;

                } else {
                    $data = [];
                }
            } else {
                $data = [];
            }
        }

        // array 合併
        $arrayData = $this->array_merge($data);

        $jsonData = [
            // 'num'  => array_sum($yearData),
            'time' => array_keys($arrayData),
            'data' => array_values($arrayData),
        ];

        return $jsonData;
    }

    /**
     * 共用function
     * 適用對象 school
     * 適用參數 teachlogintable subjectnum examinationnum   studyLoginTable
     * $ApiData='' 參數僅限適用參數
     * 此function 僅供 單一學校使用
     */
    public function schoolDashboard($ApiData = null, $schoolId = null, $semester = null, $year = null)
    {
        if (isset($schoolId) && isset($semester) && isset($year)) {
            $ApiDistricts = DB::table('ApiDistricts')->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->get();
        } elseif (isset($schoolId)) {
            //學區
            $ApiDistricts = DB::table('ApiDistricts')->select('year', 'teachlogintable', 'subjectnum', 'examinationnum', 'textbooknum', 'studylogintable')
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->get();
        } else {
            $ApiDistricts = null;
        }

        //判段資料要超過0才會成立
        if (count($ApiDistricts)) {
            foreach ($ApiDistricts as $ApiDistrict) {
                //取json值
                $yearApiDistricts = json_decode($ApiDistrict->$ApiData, true);

                if ($yearApiDistricts != 0) {
                    foreach ($yearApiDistricts as $yearApiDistrict) {
                        if (isset($yearData[$yearApiDistrict['year']])) {
                            $yearData[$yearApiDistrict['year']] += $yearApiDistrict['total'];
                        } else {
                            $yearData[$yearApiDistrict['year']] = $yearApiDistrict['total'];
                        }
                    }
                } else {
                    $yearData = null;
                }
            }

            if ($yearData != null) {
                //排除 空值
                $yearData = array_filter($yearData);
                //排序
                ksort($yearData);

                $data = $yearData;

            } else {
                $data = [];
            }
        } else {
            $data = [];
        }


        $jsonData = [
            'num'  => array_sum($data),
            'time' => array_keys($data),
            'data' => array_values($data),
        ];


        return $jsonData;
    }

    /**
     * 共用function
     * 適用對象 school
     * 適用參數 chartData  curriculum uploadMovie production overturnClass allLearningProcess
     * $ApiData='' 參數僅限適用參數
     * 此function 僅供 單一學校使用
     */
    public function schoolChartData($ApiData = null, $schoolId = null, $semester = null, $year = null)
    {
        if (isset($schoolId) && isset($semester) && isset($year)) {
            //學區
            $ApiDistricts = DB::table('ApiDistricts')->selectRaw("sum($ApiData) as total,year")
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->groupBy('year')
                ->get();
        } elseif (isset($schoolId)) {
            //學區
            $ApiDistricts = DB::table('ApiDistricts')->selectRaw("sum($ApiData) as total,year")
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->groupBy('year')
                ->get();
        } else {
            $ApiDistricts = null;
        }
        //判段資料要超過0才會成立
        if (count($ApiDistricts)) {
            if ($ApiDistricts != null) {
                foreach ($ApiDistricts as $apiDistrict) {

                    if (isset($yearData[$apiDistrict->year])) {
                        $yearData[$apiDistrict->year] += $apiDistrict->total;
                    } else {
                        $yearData[$apiDistrict->year] = $apiDistrict->total;
                    }
                }
            } else {
                $yearData = null;
            }

            if ($yearData != null) {
                //排除 空值
                $yearData = array_filter($yearData);
                //排序
                ksort($yearData);
                //存所有學校資訊
                $data = $yearData;
            } else {
                $data = [];
            }
        } else {
            $data = [];
        }


        // array 合併
        // $arrayData = $this->array_merge($data);

        $jsonData = [
            // 'num'  => array_sum($yearData),
            'time' => array_keys($data),
            'data' => array_values($data),
        ];

        return $jsonData;
    }

    /**
     * 共用function
     * 適用對象 school
     * 適用參數 underway unfinished achieve
     * $ApiData='' 參數僅限適用參數
     * 此function 僅供 單一學校使用
     * 單一學校無法使用
     */
    public function schoolStudy($ApiData = null, $schoolId = null, $semester = null, $year = null)
    {
        //學區
        if (isset($schoolId) && isset($semester) && isset($year)) {
            $ApiDistricts = DB::table('ApiDistricts')->select('year', 'underway', 'unfinished', 'achieve')
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->where('semester', $semester)
                ->where('year', $year)
                ->get();
        } elseif (isset($schoolId)) {
            $ApiDistricts = DB::table('ApiDistricts')->select('year', 'underway', 'unfinished', 'achieve')
                ->where('schoolId', '=', ($schoolId == 0) ? 0 : $schoolId)
                ->where('year', '!=', 0)
                ->get();
        } else {
            $ApiDistricts = null;
        }

        //判段資料要超過0才會成立
        if (count($ApiDistricts)) {
            foreach ($ApiDistricts as $ApiDistrict) {
                //取json值
                $yearApiDistricts = json_decode($ApiDistrict->$ApiData, true);
                if ($yearApiDistricts != null) {
                    foreach ($yearApiDistricts as $yearApiDistrict) {

                        if (isset($yearData[$yearApiDistrict['year']])) {
                            $yearData[$yearApiDistrict['year']] += $yearApiDistrict['total'];
                        } else {
                            $yearData[$yearApiDistrict['year']] = $yearApiDistrict['total'];
                        }
                    }
                } else {
                    $yearData = null;
                }
            }
            if ($yearData != null) {
                //排除 空值
                $yearData = array_filter($yearData);
                //排序
                ksort($yearData);
                //存全部學校array
                $data = $yearData;
            } else {
                $data = [];
            }
        } else {
            $data = [];
        }


        $jsonData = [
            'percentage' => array_sum($data) / 1000,
            'time'       => array_keys($data),
            'data'       => array_values($data),
        ];

        return $jsonData;
    }

    /**
     * 共用function array merge
     * 適用條件 多筆array
     *
     */
    public function array_merge($arrayData)
    {

        $v = array();

        foreach ($arrayData AS $data) {
            foreach ($data as $key => $value) {
                if (isset($v[$key])) {
                    $v[$key] += $value;
                } else {
                    $v[$key] = $value;
                }
            }
        }
        return $v;
    }








































}
