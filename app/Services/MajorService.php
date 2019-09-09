<?php

namespace App\Services;

use App\Models\Course;
use App\Repositories\CourseRepository;
use App\Repositories\MajorRepository;
use Illuminate\Http\JsonResponse;
use phpDocumentor\Reflection\Types\Object_;
use Yish\Generators\Foundation\Service\Service;

class MajorService extends Service
{
    protected $majorRepository;

    protected $courseRepository;

    public function __construct(MajorRepository $majorRepository, CourseRepository $courseRepository)
    {
        $this->majorRepository  = $majorRepository;
        $this->courseRepository = $courseRepository;
    }


    /**
     * @param $CourseNO
     * @param $student
     * @return bool|int
     */
    public function updateByMajor($CourseNO, $student)
    {
        if ($this->courseRepository->findWhere(['CourseNO' => $CourseNO, 'manageby' => 'T'])->isNotEmpty()) {
            return $this->majorRepository->updateByT($CourseNO, $student);
        }

        if ($this->courseRepository->findWhere(['CourseNO' => $CourseNO, 'manageby' => 'S'])->isNotEmpty()) {
            return $this->majorRepository->updateByS($CourseNO, $student);
        }
        return false;
    }

    /**
     * @param $courseNO
     * @param object $studentsCollection
     * @return JsonResponse
     */
    public function studentFormatInspectByUpdateMajor($courseNO, $studentsCollection)
    {
        return $studentsCollection->each(function ($student) use ($courseNO) {
            $student = (object)$student;
            $this->updateByMajor($courseNO, $student);
        })->isNotEmpty();
    }
}
