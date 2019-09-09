<?php

namespace App\Http\Requests\Api\V1\Messages;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * App\Http\Controllers\Api\V1\Messages\MessageController index request
 *
 * @package App\Http\Requests\Api\V1\Courses
 */
class GetMessageRequest extends BaseApiV1Request
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
            'limit' => 'integer|between:1,30',
            'next_id' => 'string|max:100',
            'prev_id' => 'string|max:100',
        ];
    }
}