<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\ClassService;

class ClassController extends BaseController
{
    private ClassService $service;

    public function __construct()
    {
        $this->service = new ClassService();
    }

    public function store(array $params): void
    {
        $class = $this->service->create((int)$params['courseId'], $this->getBody());
        Response::created($class);
    }

    public function update(array $params): void
    {
        $class = $this->service->update(
            (int)$params['courseId'],
            (int)$params['classId'],
            $this->getBody()
        );
        Response::json($class);
    }

    public function destroy(array $params): void
    {
        $this->service->delete((int)$params['courseId'], (int)$params['classId']);
        Response::noContent();
    }
}
