<?php

namespace App\Http\Requests\Api\V1\TeamModel;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * Class DestroyUserRequest
 *
 * @package App\Http\Requests\Api\V1\Events
 */
class DestroyUserRequest extends  BaseApiV1Request
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
            'id' => 'required|string|max:100',
        ];
    }
}