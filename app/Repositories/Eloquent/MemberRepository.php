<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class MemberRepository
 *
 * @package App\Repositories\Eloquent
 */
class MemberRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\MemberEntity";
    }

    public function boot()
    {

    }

    /**
     * 查詢使用者個人資料
     *
     * @param integer $memberID
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForUser($memberID)
    {
        $model = $this->model->select('member.*', 'systemauthority.*', 'schoolinfo.SchoolName', 'schoolinfo.CreateDate', 'schoolinfo.EndDate', 'schoolinfo.SchoolCode')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->join('schoolinfo', 'member.SchoolID', '=', 'schoolinfo.SchoolID')
            ->where('member.Status', 1)
            ->where('member.MemberID', $memberID)
            ->first();

        $this->resetModel();

        return $model;
    }

    /**
     * 查詢老師個人資料，包含各個 Status 狀態
     *
     * @param integer $memberID
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findForAllStatusTeacher($memberID)
    {
        $model = $this->model->select('member.*', 'systemauthority.*', 'schoolinfo.SchoolName')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->join('schoolinfo', 'member.SchoolID', '=', 'schoolinfo.SchoolID')
            ->where('member.MemberID', $memberID)
            ->where('systemauthority.IDLevel', 'T')
            ->first();

        $this->resetModel();

        return $model;
    }

    /**
     * 查詢學校所有擁有銷售授權的老師資料
     *
     * @param integer $schoolId
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function findAllSalesTeacherInSchool($schoolId)
    {
        $model = $this->model->select('member.*', 'systemauthority.*')
            ->join('systemauthority', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->where('member.SchoolID', $schoolId)
            ->where('member.Status', 1)
            ->where('systemauthority.IDLevel', 'T')
            ->where('systemauthority.authorization_type', 0)
            ->get();

        $this->resetModel();

        return $model;
    }

    /**
     * 計算啟用碼使用人數
     *
     * @param $activation_code_id 啟用碼 ID
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function countUserByActivationCodeId($activation_code_id)
    {
        $model = $this->model->where('activation_code_id', $activation_code_id)
            ->where('Status', 1)
            ->count();

        $this->resetModel();

        return $model;
    }
}