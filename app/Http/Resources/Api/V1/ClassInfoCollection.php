<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClassInfoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($classInfo) {
            return [
                'CNO'       => (int)$classInfo->CNO,
                'GradeName' => $classInfo->GradeName,
            ];
        })->toArray();
    }
}
