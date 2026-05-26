<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ClassController;
use App\Controllers\CourseController;
use App\Controllers\EnrollmentController;
use App\Controllers\UserController;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\RateLimiter;
use App\Helpers\Response;
use App\Router;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

RateLimiter::check();

$router = new Router();

$router->get('/api/health', fn($p) => Response::json(['status' => 'ok']));

$router->get('/api/courses', [CourseController::class, 'index']);
$router->post('/api/courses', [CourseController::class, 'store']);
$router->put('/api/courses/{id}', [CourseController::class, 'update']);
$router->delete('/api/courses/{id}', [CourseController::class, 'destroy']);

$router->post('/api/courses/{courseId}/classes', [ClassController::class, 'store']);
$router->put('/api/courses/{courseId}/classes/{classId}', [ClassController::class, 'update']);
$router->delete('/api/courses/{courseId}/classes/{classId}', [ClassController::class, 'destroy']);

$router->post('/api/users', [UserController::class, 'store']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy']);
$router->get('/api/users/{id}/enrollments', [EnrollmentController::class, 'userEnrollments']);

$router->post('/api/enrollments', [EnrollmentController::class, 'store']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (NotFoundException $e) {
    Response::error(404, 'NOT_FOUND', $e->getMessage());
} catch (ValidationException $e) {
    Response::error(422, 'VALIDATION_ERROR', $e->getMessage(), $e->getDetails());
} catch (BusinessRuleException $e) {
    $code = match ($e->getRule()) {
        'EMAIL_ALREADY_EXISTS'       => 409,
        'ENROLLMENT_DUPLICATE_COURSE' => 409,
        default                      => 422,
    };
    Response::error($code, $e->getRule() ?: 'BUSINESS_RULE_VIOLATION', $e->getMessage());
} catch (\PDOException $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'unique') || str_contains($msg, 'duplicate')) {
        Response::error(409, 'CONFLICT', 'Registro duplicado.');
    } else {
        Response::error(500, 'INTERNAL_ERROR', 'Erro interno do servidor.');
    }
} catch (\Throwable $e) {
    Response::error(500, 'INTERNAL_ERROR', 'Erro interno do servidor.');
}
