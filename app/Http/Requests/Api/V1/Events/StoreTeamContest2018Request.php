<?php

namespace App\Http\Requests\Api\V1\Events;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * Class StoreTeamContest2018Request
 *
 * @package App\Http\Requests\Api\V1\Events
 */
class StoreTeamContest2018Request extends  BaseApiV1Request
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
            'user_name' => 'required|string|max:50',
        ];
    }
}