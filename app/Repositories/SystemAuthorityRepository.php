<?php

namespace App\Repositories;

use App\Models\SystemAuthority;
use Yish\Generators\Foundation\Repository\Repository;
use App\Repositories\Base\BaseRepository;

class SystemAuthorityRepository extends BaseRepository
{
    protected $model;

    public function __construct(SystemAuthority $systemAuthority)
    {
        $this->model = $systemAuthority;
    }

    /**
     * 更新學校所有銷售授權的老師，使用空間為預設
     *
     * @param integer $schoolId 學校 ID
     *
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

        return;
    }

    /**
     * 更新學校所有銷售授權的老師，蘇格拉底權限為預設
     *
     * @param integer $schoolId 學校 ID
     * @param array $analysis 欲被更改的蘇格拉底授權
     *
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

        return;
    }
}
