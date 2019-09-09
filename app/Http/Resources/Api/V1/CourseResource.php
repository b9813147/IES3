<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

class CourseResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'CNO'        => $this->CNO,
            'SNO'        => $this->SNO,
            'CourseNO'   => $this->CourseNO,
            'CourseCode' => $this->CourseCode,
            'CourseName' => $this->CourseName,
            'Subject'    => $this->Subject,
            'majorcode'  => $this->majorcode,
            'validdt'    => $this->validdt,
            'tmcourse'   => $this->tmcourse,
            //                'mod_courseinfo' => ($course->manageby == 'T') ? true : false,
            'mod_courseinfo' => false,
            'mod_seat_no'    => ($this->manageby == 'T') ? true : false,
            'mod_majorcode'  => ($this->manageby == 'T' && $this->tmcourse == 1) ? true : false,
        ];
    }
}
