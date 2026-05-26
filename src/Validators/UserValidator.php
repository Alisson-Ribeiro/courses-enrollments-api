<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

class UserValidator
{
    public static function validateCreate(array $data): void
    {
        $errors = [];

        if (empty($data['name']) || !is_string($data['name'])) {
            $errors['name'] = 'O nome é obrigatório.';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'O nome deve ter no máximo 255 caracteres.';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'O email é obrigatório.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'O email informado é inválido.';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos.', $errors);
        }
    }
}
