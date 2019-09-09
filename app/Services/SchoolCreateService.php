<?php

namespace App\Services;

use App\Repositories\Eloquent\SchoolInfoRepository;
use App\Supports\TimeSupport;

/**
 * 創建學校的相關 Service
 *
 * @package App\Services
 */
class SchoolCreateService
{
    use TimeSupport;

    /** @var SchoolInfoRepository */
    protected $schoolInfoRepository;

    /** @var array 創建學校的預設資料*/
    protected $defaultInsertSchoolData;

    /**
     * SchoolCreateService constructor.
     *
     * @param SchoolInfoRepository $schoolInfoRepository
     */
    public function __construct(
        SchoolInfoRepository $schoolInfoRepository
    )
    {
        $this->schoolInfoRepository = $schoolInfoRepository;

        // 創建學校的預設資料
        $this->defaultInsertSchoolData = [
            'school_name' => 'School',
            'create_date' => $this->currentDateString(),
            'end_date' => null,
            'max_teacher' => 0,
            'max_aclassone' => 0
        ];
    }

    /**
     * 建立學校
     *
     * @param string $schoolCode 學校代碼
     * @param string $abbr 學校簡碼
     * @param array $createSchoolData $this->defaultInsertSchoolData
     *
     * @return mixed
     *
     * @throws \App\Exceptions\RepositoryException
     */
    public function createSchool($schoolCode, $abbr, $createSchoolData)
    {
        $createSchoolData = array_merge($this->defaultInsertSchoolData, $createSchoolData);

        // 建立學校
        return $this->schoolInfoRepository->createSchoolByCodeOrAbbr(
            $schoolCode,
            $abbr,
            [
                'SchoolName' => $createSchoolData['school_name'],
                'CreateDate' => $createSchoolData['create_date'],
                'EndDate' => $createSchoolData['end_date'],
                'MaxTeacher' => $createSchoolData['max_teacher'],
                'MaxAclassone' => $createSchoolData['max_aclassone']
            ]
        );
    }
}
