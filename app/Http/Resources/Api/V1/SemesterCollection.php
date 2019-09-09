<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class SemesterCollection
 *
 * @package App\Http\Resources\Api\V1
 */
class SemesterCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($semester) {
            return [
                'sno' => $semester->SNO,
                'academic_year' => $semester->AcademicYear,
                'semester' => $semester->SOrder,
                'is_current' => $semester->is_current
            ];
        })->toArray();
    }
}