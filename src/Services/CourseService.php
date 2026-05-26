<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\CourseRepository;
use App\Validators\CourseValidator;

class CourseService
{
    private CourseRepository $repository;

    public function __construct()
    {
        $this->repository = new CourseRepository();
    }

    public function create(array $data): array
    {
        CourseValidator::validateCreate($data);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): array
    {
        $this->findOrFail($id);
        CourseValidator::validateUpdate($data);
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id);
        $this->repository->delete($id);
    }

    public function listWithAvailableClasses(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->findAllWithAvailableClasses($filters, $page, $perPage);
    }

    private function findOrFail(int $id): array
    {
        $course = $this->repository->findById($id);
        if ($course === null) {
            throw new NotFoundException("Curso {$id} não encontrado.");
        }
        return $course;
    }
}
