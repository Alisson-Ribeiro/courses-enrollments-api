<?php

namespace App\Helpers;

class IdempotencyHandler
{
    private const TTL     = 86400; // 24 horas
    private const MAX_KEY = 128;

    public static function isValidKey(string $key): bool
    {
        return $key !== '' && strlen($key) <= self::MAX_KEY;
    }

    public static function get(string $key): ?array
    {
        $cacheKey = self::cacheKey($key);

        if (extension_loaded('apcu') && apcu_enabled()) {
            $data = apcu_fetch($cacheKey, $exists);
            return $exists ? $data : null;
        }

        return self::getFromFile($cacheKey);
    }

    public static function store(string $key, int $status, string $body): void
    {
        $cacheKey = self::cacheKey($key);
        $data     = ['status' => $status, 'body' => $body];

        if (extension_loaded('apcu') && apcu_enabled()) {
            apcu_store($cacheKey, $data, self::TTL);
            return;
        }

        self::storeInFile($cacheKey, $data);
    }

    private static function cacheKey(string $key): string
    {
        return 'ik_' . hash('sha256', $key);
    }

    private static function filePath(string $cacheKey): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $cacheKey . '.json';
    }

    private static function getFromFile(string $cacheKey): ?array
    {
        $path   = self::filePath($cacheKey);
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            return null;
        }

        flock($handle, LOCK_SH);
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $data = json_decode($contents, true);
        if (!is_array($data) || !isset($data['expires_at'], $data['status'], $data['body'])) {
            return null;
        }

        if (time() >= $data['expires_at']) {
            return null;
        }

        return ['status' => $data['status'], 'body' => $data['body']];
    }

    private static function storeInFile(string $cacheKey, array $data): void
    {
        $path   = self::filePath($cacheKey);
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return; // filesystem indisponível — fail open
        }

        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode([
            'status'     => $data['status'],
            'body'       => $data['body'],
            'expires_at' => time() + self::TTL,
        ]));
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
