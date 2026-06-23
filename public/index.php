<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ClassController;
use App\Controllers\CourseController;
use App\Controllers\EnrollmentController;
use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\IdempotencyHandler;
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

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestPath !== '/api/health') {
    RateLimiter::check();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
    if ($rawKey !== '' && IdempotencyHandler::isValidKey($rawKey)) {
        $cached = IdempotencyHandler::get($rawKey);
        if ($cached !== null) {
            http_response_code($cached['status']);
            echo $cached['body'];
            exit;
        }
        // Reserva atomicamente a chave via Redis SET NX — elimina race condition entre réplicas.
        // Se outra instância já pegou o lock, aguarda 150ms e tenta ler o resultado cacheado.
        $reserved = IdempotencyHandler::reserve($rawKey);
        if (!$reserved) {
            usleep(150_000);
            $cached = IdempotencyHandler::get($rawKey);
            if ($cached !== null) {
                http_response_code($cached['status']);
                echo $cached['body'];
                exit;
            }
            // Redis indisponível ou tempo de processamento excedeu o lock — segue normalmente.
        }
        // ob_start + register_shutdown_function: única forma de capturar o corpo da resposta
        // depois que os headers já foram enviados pelo PHP. O shutdown é executado após o
        // script terminar e os dados são persistidos no cache somente para respostas não-5xx.
        ob_start();
        register_shutdown_function(function () use ($rawKey) {
            $body   = ob_get_contents();
            ob_end_flush();
            $status = http_response_code();
            if ($status < 500) {
                IdempotencyHandler::store($rawKey, $status, $body);
            }
        });
    }
}

$router = new Router();

$router->get('/api/health', [HealthController::class, 'index']);

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
$router->delete('/api/enrollments/{id}', [EnrollmentController::class, 'destroy']);

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
