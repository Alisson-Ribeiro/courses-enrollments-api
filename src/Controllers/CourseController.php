<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\CourseService;

class CourseController extends BaseController
{
    private CourseService $service;

    public function __construct()
    {
        $this->service = new CourseService();
    }

    public function index(array $params): void
    {
        $filters = [];
        if (!empty($_GET['title'])) {
            $filters['title'] = $_GET['title'];
        }
        if (!empty($_GET['topic'])) {
            $filters['topic'] = $_GET['topic'];
        }

        $courses = $this->service->listWithAvailableClasses($filters);
        Response::json(['data' => $courses, 'total' => count($courses)]);
    }

    public function store(array $params): void
    {
        $course = $this->service->create($this->getBody());
        Response::created($course);
    }

    public function update(array $params): void
    {
        $course = $this->service->update((int)$params['id'], $this->getBody());
        Response::json($course);
    }

    public function destroy(array $params): void
    {
        $this->service->delete((int)$params['id']);
        Response::noContent();
    }
}
