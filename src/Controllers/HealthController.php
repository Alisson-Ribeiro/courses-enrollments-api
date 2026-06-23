<?php

namespace App\Controllers;

use App\Config\Database;

class HealthController extends BaseController
{
    public function index(array $params): void
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'opcache'  => $this->checkOpcache(),
            'apcu'     => $this->checkApcu(),
            'redis'    => $this->checkRedis(),
        ];

        $allOk = array_reduce(
            $checks,
            fn(bool $carry, array $check) => $carry && ($check['status'] === 'ok'),
            true
        );

        http_response_code($allOk ? 200 : 503);
        echo json_encode([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function checkDatabase(): array
    {
        try {
            Database::getConnection()->query('SELECT 1');
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkOpcache(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['status' => 'error', 'message' => 'extensão não instalada'];
        }
        $info = opcache_get_status(false);
        if ($info === false || empty($info['opcache_enabled'])) {
            return ['status' => 'error', 'message' => 'desabilitado'];
        }
        return [
            'status'   => 'ok',
            'hit_rate' => round($info['opcache_statistics']['opcache_hit_rate'] ?? 0, 2),
        ];
    }

    private function checkApcu(): array
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            return ['status' => 'error', 'message' => 'extensão não instalada ou desabilitada'];
        }
        return ['status' => 'ok'];
    }

    private function checkRedis(): array
    {
        $host = getenv('REDIS_HOST') ?: null;
        if ($host === null || !extension_loaded('redis')) {
            return ['status' => 'error', 'message' => 'não configurado ou extensão ausente'];
        }
        try {
            $r = new \Redis();
            $r->connect($host, (int)(getenv('REDIS_PORT') ?: 6379));
            $r->ping();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
