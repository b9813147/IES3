<?php

namespace App\Repositories\Eloquent;

use App\Repositories\BaseRepository;

/**
 * Class SystemAuthorityRepository
 *
 * @package App\Repositories\Eloquent
 */
class SystemAuthorityRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return "App\\Entities\\SystemAuthorityEntity";
    }

    public function boot()
    {

    }

    /**
     * 更新學校所有銷售授權的老師，使用空間為預設
     *
     * @param integer $schoolId 學校 ID
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateTeacherEzCmsSizeToDefault($schoolId)
    {
        $this->model->join('member', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->where('member.SchoolID', $schoolId)
            ->where('member.Status', 1)
            ->where('systemauthority.IDLevel', 'T')
            ->where('systemauthority.authorization_type', 0)
            ->update([
                'systemauthority.EzcmsSize' => 0,
            ]);

        $this->resetModel();

        return;
    }

    /**
     * 更新學校所有銷售授權的老師，蘇格拉底權限為預設
     *
     * @param integer $schoolId 學校 ID
     * @param array $analysis 欲被更改的蘇格拉底授權
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function updateTeacherAnalysisToDefault($schoolId, $analysis)
    {
        $this->model->join('member', 'member.MemberID', '=', 'systemauthority.MemberID')
            ->where('member.SchoolID', $schoolId)
            ->where('member.Status', 1)
            ->where('systemauthority.IDLevel', 'T')
            ->where('systemauthority.authorization_type', 0)
            ->whereIn('systemauthority.Analysis', $analysis)
            ->update([
                'systemauthority.Analysis' => 0,
            ]);

        $this->resetModel();

        return;
    }
}