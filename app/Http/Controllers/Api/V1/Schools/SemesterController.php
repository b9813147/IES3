<?php

namespace App\Http\Controllers\Api\V1\Schools;

use Illuminate\Http\Request;
use App\Services\SemesterService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Http\Resources\Api\V1\SemesterCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 學年學期資料
 *
 * @package App\Http\Controllers\Api\V1\Schools
 */
class SemesterController extends BaseApiV1Controller
{
    /**
     * SemesterController constructor.
     */
    public function __construct()
    {

    }

    /**
     * 查詢學年學期資料清單
     *
     * @param SemesterService $service
     *
     * @return SemesterCollection
     */
    public function index(SemesterService $service)
    {
        try {
            // 取得所有學年、學期
            $semesters = $service->getAllSemesters();
            SemesterCollection::wrap('semesters');
            return new SemesterCollection($semesters);
        } catch (\Exception $e) {
            throw new HttpException(500, '');
        }
    }
}