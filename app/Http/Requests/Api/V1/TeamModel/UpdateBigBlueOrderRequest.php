<?php

namespace App\Http\Requests\Api\V1\TeamModel;

use App\Http\Requests\Api\V1\BaseApiV1Request;

/**
 * Class DestroyUserRequest
 *
 * @package App\Http\Requests\Api\V1\Events
 */
class UpdateBigBlueOrderRequest extends  BaseApiV1Request
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
            'msg.schoolCode' => 'required|string|max:20',
            'msg.schoolShortCode' => 'required|string|max:50',
            'msg.schoolName' => 'required|string|max:50',
            'msg.orderid' => 'required|string|max:30',
            'msg.orderAudit' => 'required|integer|max:100',
            'msg.teamModelId' => 'required_if:msg.periodtype,1|nullable|array',
            'msg.teamModelId.*' => 'required_if:msg.periodtype,1|distinct|string|max:120',
            'msg.periodid' => 'required|integer|max:2147483647',
            'msg.periodtype' => 'required|integer|in:0,1',
            'msg.startDate' => 'required|date_format:Y-m-d',
            'msg.endDate' => 'required|date_format:Y-m-d|after_or_equal:msg.startDate',
            'msg.prod.*.prodcode' => 'distinct|required|string|max:100',
            'msg.prod.*.saleType' => 'required|integer|max:100',
            'msg.prod.*.number' => 'required|integer|max:2147483647',
            'msg.prod.*.unit' => 'nullable|string|max:10',
        ];
    }
}