<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

class CourseValidator
{
    public const VALID_TOPICS = ['inovacao', 'tecnologia', 'marketing', 'empreendedorismo', 'agro'];

    public static function validateCreate(array $data): void
    {
        $errors = [];

        if (empty($data['title']) || !is_string($data['title'])) {
            $errors['title'] = 'O título é obrigatório.';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'O título deve ter no máximo 255 caracteres.';
        }

        if (empty($data['topic'])) {
            $errors['topic'] = 'O tema é obrigatório.';
        } elseif (!in_array($data['topic'], self::VALID_TOPICS, true)) {
            $errors['topic'] = 'Tema inválido. Valores aceitos: ' . implode(', ', self::VALID_TOPICS) . '.';
        }

        if (isset($data['image_url']) && $data['image_url'] !== null && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $errors['image_url'] = 'A URL da imagem é inválida.';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos.', $errors);
        }
    }

    public static function validateUpdate(array $data): void
    {
        $errors = [];

        if (array_key_exists('title', $data)) {
            if (empty($data['title']) || !is_string($data['title'])) {
                $errors['title'] = 'O título não pode ser vazio.';
            } elseif (strlen($data['title']) > 255) {
                $errors['title'] = 'O título deve ter no máximo 255 caracteres.';
            }
        }

        if (array_key_exists('topic', $data)) {
            if (!in_array($data['topic'], self::VALID_TOPICS, true)) {
                $errors['topic'] = 'Tema inválido. Valores aceitos: ' . implode(', ', self::VALID_TOPICS) . '.';
            }
        }

        if (isset($data['image_url']) && $data['image_url'] !== null && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $errors['image_url'] = 'A URL da imagem é inválida.';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos.', $errors);
        }
    }
}
