<?php

namespace App\Http\Requests\Api\V1\Courses;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * App\Http\Controllers\Api\V1\Courses\CourseController index request
 *
 * @package App\Http\Requests\Api\V1\Courses
 */
class GetCourseRequest extends BaseApiV1Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 以逗號分隔並限制總組數量，此為限制 10 組數字，每組限制 6 位數
            'sno' => 'regex:/^(\s*\d{1,6}\s*[,]){0,9}\s*\d{1,6}$/'
        ];
    }
}