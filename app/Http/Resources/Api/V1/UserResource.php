<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class UserResource
 *
 * @package App\Http\Resources\Api\V1
 */
class UserResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'level' => $this->IDLevel,
            'login_id' => $this->LoginID,
            'name' => $this->RealName,
            'nickname' => $this->NickName,
            'gender' => (strtoupper($this->Gender) === 'F') ? 'female' : 'male',
            'birthday' => date('Y-m-d', strtotime($this->Birthday)),
            'organization' => $this->department,
            'telephone' => $this->Telephone,
            'mobile_phone' => $this->cellphone,
            'address' => $this->Address,
            'email' => $this->Email,
            'website' => $this->Homepage,
            'school_name' => $this->SchoolName,
            'photo_url' => $this->photo_url
        ];
    }
}