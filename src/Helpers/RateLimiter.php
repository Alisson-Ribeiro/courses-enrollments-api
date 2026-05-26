<?php

namespace App\Helpers;

class RateLimiter
{
    private const MAX_REQUESTS = 60;
    private const WINDOW_SECONDS = 60;

    public static function check(): void
    {
        if (!extension_loaded('apcu') || !apcu_enabled()) {
            return;
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rl_' . $ip;

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
