<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\ClassRepository;
use App\Repositories\CourseRepository;
use App\Validators\ClassValidator;

class ClassService
{
    private ClassRepository  $classRepository;
    private CourseRepository $courseRepository;

    public function __construct()
    {
        $this->classRepository  = new ClassRepository();
        $this->courseRepository = new CourseRepository();
    }

    public function create(int $courseId, array $data): array
    {
        if ($this->courseRepository->findById($courseId) === null) {
            throw new NotFoundException("Curso {$courseId} não encontrado.");
        }

        ClassValidator::validateCreate($data);
        return $this->classRepository->create($courseId, $data);
    }

    public function update(int $courseId, int $classId, array $data): array
    {
        $this->findOrFail($courseId, $classId);
        ClassValidator::validateUpdate($data);
        return $this->classRepository->update($classId, $data);
    }

    public function delete(int $courseId, int $classId): void
    {
        $class = $this->classRepository->findByCourseAndId($courseId, $classId);
        if ($class === null) return;
        $this->classRepository->delete($classId);
    }

    private function findOrFail(int $courseId, int $classId): array
    {
        if ($this->courseRepository->findById($courseId) === null) {
            throw new NotFoundException("Curso {$courseId} não encontrado.");
        }

        $class = $this->classRepository->findByCourseAndId($courseId, $classId);
        if ($class === null) {
            throw new NotFoundException("Turma {$classId} não encontrada no curso {$courseId}.");
        }

        return $class;
    }
}
