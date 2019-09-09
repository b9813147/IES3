<?php

namespace App\Services;

use App\Repositories\Eloquent\SemesterInfoRepository;
use App\Constants\Models\SemesterInfoConstant;

/**
 * 查詢學年學期相關 Service
 *
 * @package App\Services
 */
class SemesterService
{
    /** @var SemesterInfoRepository */
    protected $repository;

    /** @var \Illuminate\Config\Repository|mixed */
    protected $academicYearPoint;

    /** @var \Illuminate\Config\Repository|mixed */
    protected $semesterSOrderPoint;

    /**
     * SemesterService constructor.
     *
     * @param SemesterInfoRepository $repository
     */
    public function __construct(SemesterInfoRepository $repository)
    {
        $this->academicYearPoint = config('ies.academic_year_point');
        $this->semesterSOrderPoint = config('ies.semester_s_order_point');
        $this->repository = $repository;
    }

    /**
     * 取得所有學年學期
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAllSemesters()
    {
        // 取得當前學年學期
        $currentSemester = $this->getCurrentSemester();

        // 取所有學年學期
        $semesters = $this->repository->all();

        // 設定參數 is_current：判斷是否為當前學年學期
        foreach ($semesters as $semester) {
            $semester->is_current = false;
            if ($semester->SNO == $currentSemester->SNO) {
                $semester->is_current = true;
            }
        }

        return $semesters;
    }

    /**
     * 取得當前日期的學年學期
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function getCurrentSemester()
    {
        // 當前年份和月份
        $year = intval(date("Y"));
        $month = intval(date("m"));

        // 取得學年和學期別
        $academicYear = $this->getAcademicYear($year, $month);
        $sOrder = $this->getSemesterSOrder($month);

        // 查詢 DB，如果沒有則新增一筆
        return $this->repository->firstOrCreate(['AcademicYear' => $academicYear, 'SOrder' => $sOrder]);
    }

    /**
     * 取得學年
     *
     * 依傳入的年份和月份查詢學年
     *
     * @param $year  年份
     * @param $month 月份
     * @return int
     */
    private function getAcademicYear($year, $month)
    {
        if ($month < $this->semesterSOrderPoint || ($month >= $this->semesterSOrderPoint && $month < $this->academicYearPoint)) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * 取得學期
     *
     * 依傳入的月份查詢學期
     *
     * @param $month 月份
     * @return int
     */
    private function getSemesterSOrder($month)
    {
        // 上學期
        if ($month < $this->semesterSOrderPoint || $month >= $this->academicYearPoint) {
            return SemesterInfoConstant::S_ORDER_FIRST;
        }

        // 下學期
        return SemesterInfoConstant::S_ORDER_SECOND;
    }
}