<?php

namespace App\Controllers;

use App\Exceptions\ValidationException;

abstract class BaseController
{
    protected function getBody(): array
    {
        $json = file_get_contents('php://input');
        if (empty(trim($json))) {
            return [];
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('JSON inválido: ' . json_last_error_msg());
        }

        return $data ?? [];
    }
}
