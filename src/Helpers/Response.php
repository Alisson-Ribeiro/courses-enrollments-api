<?php

namespace App\Helpers;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public static function created(mixed $data): void
    {
        self::json($data, 201);
    }

    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    public static function error(int $status, string $code, string $message, array $details = []): void
    {
        $body = [
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if (!empty($details)) {
            $body['error']['details'] = $details;
        }

        self::json($body, $status);
    }
}
