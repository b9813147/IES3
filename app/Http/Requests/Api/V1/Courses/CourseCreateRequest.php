<?php

namespace App\Http\Requests\Api\V1\Courses;

use App\Http\Requests\Api\V1\BaseApiV1Request;

class CourseCreateRequest extends BaseApiV1Request
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
            'CNO'        => 'required|integer',
            'SNO'        => 'required|integer',
            'CourseCode' => 'max:128',
            'CourseName' => 'string|max:255|required',
            'Subject'    => 'string|max:255|required',
            'majorcode'  => 'max:6',
            'validdt'    => 'date',
            'Type'       => 'max:50',
            'tmcourse'   => 'integer|max:11',
            'manageby'   => 'string|max:3',
        ];
    }
}
