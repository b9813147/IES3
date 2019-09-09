<?php

namespace App\Http\Requests\Api\V1\Messages;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * App\Http\Controllers\Api\V1\Messages\MessageController store request
 *
 * @package App\Http\Requests\Api\V1\Messages
 */
class StoreMessageRequest extends  BaseApiV1Request
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
            'recipient.course.course_no' => 'required|integer|min:1',
            'recipient.course.students' => 'sometimes|nullable|array',
            'recipient.course.students.*.member_id' => 'required_with:recipient.course.students|integer|min:1',
            'message.title' => 'required|string|max:80',
            'message.content' => 'required|string|max:200',
        ];
    }
}