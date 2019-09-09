<?php
/**
 * Created by PhpStorm.
 * User: ares
 * Date: 2018/11/12
 * Time: 下午2:32
 */

namespace App\Services;


use App\Models\Districts;
use App\Models\DistrictsAllSchool;
use App\Repositories\Eloquent\YunHeInfoRepository;

class YunHeInfoService
{
    private $yunHeInfoRepository = null;


    public function __construct(YunHeInfoRepository $yunHeInfoRepository)
    {
        $this->yunHeInfoRepository = $yunHeInfoRepository;
    }

    //取得帳號已啟用數量
    public function getMemberNum($year = null, $month = null, $school = null, $IDLevel = null)
    {
        return $this->yunHeInfoRepository->memberNum($year, $month, $school, $IDLevel);
    }

    //取得老師登入數量
    public function getTeachLoginTables($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->teachLoginTables($year, $month, $school);
    }

    //取得學生登入數量
    public function getStudyLoginTable($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->studyLoginTable($year, $month, $school);
    }

    // 課程總數
    public function getCurriculum($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->curriculum($year, $month, $school);
    }

    //老師上傳影片 數量
    public function getUploadMovie($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->uploadMovie($year, $month, $school);
    }

    public function getProduction($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->production($year, $month, $school);
    }

    //翻轉課堂數
    public function getOverturnClass($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->overturnClass($year, $month, $school);
    }

    //學習歷程總數 ExType != K  rule not like %k  模擬測驗 I 、線上測驗 A、班級競賽 J && K、HiTeach H、成績登陸 S、合併活動 L 、網路閱卷 O
    public function getAllLearningProcess($year = null, $month = null, $school = null, $exType = [])
    {
        return $this->yunHeInfoRepository->allLearningProcess($year, $month, $school, $exType);
    }

    //個人所屬 0、學區分享 2、學校分享 1 、總資源分享數 0
    public function getShareResource($year, $month, $school, $sharedLevel)
    {
        return $this->yunHeInfoRepository->shareResource($year, $month, $school, $sharedLevel);
    }

    //题目數
    public function getSubjectNum($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->subjectNum($year, $month, $school);
    }

    //試卷數
    public function getExaminationNum($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->examinationNum($year, $month, $school);
    }

    //教材數
    public function getTextbookNum($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->textbookNum($year, $month, $school);
    }

    //進行中
    public function getUnderWays($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->underWays($year, $month, $school);
    }

    //未完成
    public function getUnfinished($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->unfinished($year, $month, $school);
    }

    //完成
    public function getAchieves($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->achieves($year, $month, $school);
    }

    //分子 有做過作業的人
    public function getMolecularHomework($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->molecularHomework($year, $month, $school);
    }

    //分母 全部但不一定有做過作業
    public function getDenominatorHomework($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->denominatorHomework($year, $month, $school);
    }

    //分母 全部參與線上測驗
    public function getDenominatorOnlineTest($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->denominatorOnlineTest($year, $month, $school);
    }

    //分子 有做線上測驗的人數
    public function getMolecularOnlineTest($year = null, $month = null, $school = null)
    {
        return $this->yunHeInfoRepository->molecularOnlineTest($year, $month, $school);
    }

    //課程係補資訊
    public function getCourseDetail($year = null, $month = null)
    {
        return $this->yunHeInfoRepository->courseDetail($year, $month);
    }

    //學校資訊
    public function getSchoolInfo($school = null)
    {
        return $this->yunHeInfoRepository->schoolInfo($school);
    }

    //學區資訊
    public function getDistrictInfo($district_id)
    {
        return $this->yunHeInfoRepository->districtInfo($district_id);
    }

    //蘇格拉迪
    public function getSokratesTotal($year = null, $month = null, $school_id = null)
    {
        return $this->yunHeInfoRepository->sokrates($year, $month, $school_id);
    }

    //全部學校排序
    public function getSchoolId()
    {
        return $this->yunHeInfoRepository->schoolId();
    }

    //帳號起始年份
    public function getStartTime()
    {
        return $this->yunHeInfoRepository->startTime();
    }

    //當下年份
    public function getCurrentYear()
    {
        return $this->yunHeInfoRepository->currentYear();
    }

    //抓取區域所屬 學校
    public function getDistricts($district_id = null)
    {
        return $this->yunHeInfoRepository->districts($district_id);
    }

    //取得dashboard
    public function getDashboard($ApiData = null, $district_id = null, $schoolId = null, $semester = null, $year = null)
    {
        //檢查有無此學區
        $district = Districts::query()->where('district_id', $district_id)->exists();
        if (!$district) {
            return response()->json('無此學區');
        }
        //檢查此區域有無學校
        $school = DistrictsAllSchool::query()->where('districtID', $district_id)->select('schoolID')->distinct()->exists();

        if (!$school) {
            return response()->json('此區域無學校');
        }

        //取得單一學校資訊
        if ($schoolId) {
            //檢查傳入的schoolId存不存在
            $getSchool = DistrictsAllSchool::query()->where('districtID', $district_id)
                ->where('schoolID', $schoolId)
                ->select('schoolID')->distinct()->exists();
            if (!$getSchool) {
                return response()->json('此區域無學校');
            }

            if (isset($schoolId) && isset($semester) && isset($year)) {
                $data = $this->yunHeInfoRepository->dashboard('', $schoolId, $semester, $year);
            } elseif (isset($schoolId)) {
                $data = $this->yunHeInfoRepository->dashboard('', $schoolId);

            } else {
                $data = false;
            }

            if (boolval($data)) {
                foreach ($data as $d) {
                    //取json值
                    $DistrictData = json_decode($d->$ApiData, true);
                    if (boolval($DistrictData)) {
                        foreach ($DistrictData as $v) {
                            if (isset($yearData[$v['year']])) {
                                $yearData[$v['year']] += $v['total'];
                            } else {
                                $yearData[$v['year']] = $v['total'];
                            }
                        }
                    }
                }
                if (boolval($yearData)) {
                    //排除 空值
                    $yearData = array_filter($yearData);
                    //排序
                    ksort($yearData);
                    $json = $yearData;
                } else {
                    $json = [];
                }
            } else {
                $json = [];
            }

            $jsonData = [
                'num'  => array_sum($json),
                'time' => array_keys($json),
                'data' => array_values($json),
            ];

            return $jsonData;
        }


        //學校不重複 取得多間學校
        $schools = DistrictsAllSchool::query()
            ->where('districtID', $district_id)
            ->select('schoolID')
            ->distinct()
            ->get();
        foreach ($schools as $school) {
            //學區
            if (isset($district_id) && isset($semester) && isset($year)) {
                $data = $this->yunHeInfoRepository->dashboard($district_id, $school->schoolID, $semester, $year);

            } elseif (isset($district_id)) {
                $data = $this->yunHeInfoRepository->dashboard($district_id, $school->schoolID);

            } else {
                $data = false;
            }
            //判段資料要超過0才會成立
            if (boolval($data)) {
                foreach ($data as $d) {
                    //取json值
                    $DistrictData = json_decode($d->$ApiData, true);
                    if (boolval($DistrictData)) {
                        foreach ($DistrictData as $v) {
                            if (isset($yearData[$v['year']])) {
                                $yearData[$v['year']] += $v['total'];
                            } else {
                                $yearData[$v['year']] = $v['total'];
                            }
                        }
                    }
                }

                if (boolval($yearData)) {
                    //排除 空值
                    $yearData = array_filter($yearData);
                    //排序
                    ksort($yearData);
                    $json = $yearData;
                } else {
                    $json = [];
                }

            } else {
                $json = [];
            }
        }

        $jsonData = [
            'num'  => array_sum($json),
            'time' => array_keys($json),
            'data' => array_values($json),
        ];

        return $jsonData;


    }

}