<?php

namespace App\Http\Requests\Api\V1\Courses;

use App\Http\Requests\Api\V1\BaseApiV1Request;

class CourseMajorCodeRequest extends BaseApiV1Request
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
            'majorcode' => 'max:7',
            'validdt'   => 'required|date',
        ];
    }
}
