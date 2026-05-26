<?php

namespace App\Controllers;

use App\Exceptions\ValidationException;
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
            if (strlen($_GET['title']) > 255) {
                throw new ValidationException('O filtro title não pode exceder 255 caracteres.');
            }
            $filters['title'] = $_GET['title'];
        }
        if (!empty($_GET['topic'])) {
            $filters['topic'] = $_GET['topic'];
        }

        $page    = max(1, (int) ($_GET['page']          ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 15)));

        $result = $this->service->listWithAvailableClasses($filters, $page, $perPage);

        Response::json([
            'data' => $result['data'],
            'meta' => [
                'total'     => $result['total'],
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => (int) ceil($result['total'] / $perPage),
            ],
        ]);
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
