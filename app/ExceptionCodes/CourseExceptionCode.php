<?php

namespace App\ExceptionCodes;

/**
 * 課程相關的錯誤代碼
 *
 * @package App\ExceptionCodes
 */
abstract class CourseExceptionCode
{
    /** 課程裡沒有學生資料 */
    const COURSE_STUDENT_NOT_FOUND = 130000;
    // 課程不存在
    const COURSE_ID_NOT_FOUND =130001;
}