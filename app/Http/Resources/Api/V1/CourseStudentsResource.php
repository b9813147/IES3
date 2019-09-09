<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\Resource;

/**
 * Class CourseStudentsResource
 *
 * @package App\Http\Resources\Api\V1
 */
class CourseStudentsResource extends Resource
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
            'course_no' => (int)$this->course_no,
            'students' => new CourseStudentCollection($this->students)
        ];
    }
}