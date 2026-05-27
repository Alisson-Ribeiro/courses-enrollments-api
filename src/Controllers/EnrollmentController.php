<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\EnrollmentService;

class EnrollmentController extends BaseController
{
    private EnrollmentService $service;

    public function __construct()
    {
        $this->service = new EnrollmentService();
    }

    public function store(array $params): void
    {
        $body = $this->getBody();

        if (empty($body['user_id']) || empty($body['course_class_id'])) {
            Response::error(422, 'VALIDATION_ERROR', 'Dados inválidos.', [
                'user_id'        => empty($body['user_id']) ? 'O user_id é obrigatório.' : null,
                'course_class_id' => empty($body['course_class_id']) ? 'O course_class_id é obrigatório.' : null,
            ]);
            return;
        }

        $enrollment = $this->service->enroll(
            (int)$body['user_id'],
            (int)$body['course_class_id']
        );

        Response::created($enrollment);
    }

    public function destroy(array $params): void
    {
        $this->service->cancel((int)$params['id']);
        Response::noContent();
    }

    public function userEnrollments(array $params): void
    {
        $result = $this->service->listUserEnrollments((int)$params['id']);
        Response::json($result);
    }
}
