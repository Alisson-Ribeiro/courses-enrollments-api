<?php

namespace App\Helpers;

class RateLimiter
{
    private const MAX_REQUESTS  = 60;
    private const WINDOW_SECONDS = 60;

    private static function getRedis(): ?\Redis
    {
        $host = getenv('REDIS_HOST') ?: null;
        if ($host === null || !extension_loaded('redis')) {
            return null;
        }
        $r = new \Redis();
        $r->connect($host, (int)(getenv('REDIS_PORT') ?: 6379));
        return $r;
    }

    public static function check(): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rl_' . $ip;

        $redis = self::getRedis();
        if ($redis !== null) {
            $count = $redis->incr($key);
            if ($count === 1) {
                $redis->expire($key, self::WINDOW_SECONDS);
            }
            if ($count > self::MAX_REQUESTS) {
                header('Retry-After: ' . self::WINDOW_SECONDS);
                Response::error(429, 'TOO_MANY_REQUESTS', 'Muitas requisições. Tente novamente em 60 segundos.');
            }
            return;
        }

        // Fallback APCu (instância única)
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            return;
        }

        $count = apcu_fetch($key, $exists);
        if (!$exists) {
            apcu_store($key, 1, self::WINDOW_SECONDS);
            return;
        }
        if ($count >= self::MAX_REQUESTS) {
            header('Retry-After: ' . self::WINDOW_SECONDS);
            Response::error(429, 'TOO_MANY_REQUESTS', 'Muitas requisições. Tente novamente em 60 segundos.');
        }
        apcu_inc($key);
    }
}
