<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class CourseCollection
 *
 * @package App\Http\Resources\Api\V1
 */
class CourseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($course) {
            return [
                'sno'            => $course->SNO,
                'academic_year'  => $course->AcademicYear,
                'semester'       => (int)$course->SOrder,
                'course_no'      => $course->CourseNO,
                'course_code'    => $course->CourseCode,
                'course_name'    => $course->CourseName,
                'is_master'      => $course->is_master,
                'major_code'     => $course->majorcode,
                'validdt'        => $course->validdt,
                'tmcourse'       => $course->tmcourse,
//                'mod_courseinfo' => ($course->manageby == 'T') ? true : false,
                'mod_courseinfo' => false,
                'mod_seat_no'    => ($course->manageby == 'T') ? true : false,
                'mod_majorcode'  => ($course->manageby == 'T' && $course->tmcourse == 1) ? true : false,
            ];
        })->toArray();
    }
}