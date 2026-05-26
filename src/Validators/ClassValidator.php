<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

class ClassValidator
{
    public const VALID_STATUSES = ['disponivel', 'encerrado'];

    public static function validateCreate(array $data): void
    {
        $errors = [];

        if (empty($data['title']) || !is_string($data['title'])) {
            $errors['title'] = 'O título é obrigatório.';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'O título deve ter no máximo 255 caracteres.';
        }

        if (!isset($data['slots']) || !is_numeric($data['slots']) || (int)$data['slots'] <= 0) {
            $errors['slots'] = 'A quantidade de vagas deve ser um inteiro positivo.';
        }

        if (empty($data['status'])) {
            $errors['status'] = 'O status é obrigatório.';
        } elseif (!in_array($data['status'], self::VALID_STATUSES, true)) {
            $errors['status'] = 'Status inválido. Valores aceitos: ' . implode(', ', self::VALID_STATUSES) . '.';
        }

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'A data de início é obrigatória.';
        } elseif (!self::isValidDate($data['start_date'])) {
            $errors['start_date'] = 'Data de início inválida. Use o formato YYYY-MM-DD.';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'A data de fim é obrigatória.';
        } elseif (!self::isValidDate($data['end_date'])) {
            $errors['end_date'] = 'Data de fim inválida. Use o formato YYYY-MM-DD.';
        }

        if (empty($errors['start_date']) && empty($errors['end_date'])) {
            if ($data['end_date'] < $data['start_date']) {
                $errors['end_date'] = 'A data de fim deve ser maior ou igual à data de início.';
            }
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

        if (array_key_exists('slots', $data)) {
            if (!is_numeric($data['slots']) || (int)$data['slots'] <= 0) {
                $errors['slots'] = 'A quantidade de vagas deve ser um inteiro positivo.';
            }
        }

        if (array_key_exists('status', $data)) {
            if (!in_array($data['status'], self::VALID_STATUSES, true)) {
                $errors['status'] = 'Status inválido. Valores aceitos: ' . implode(', ', self::VALID_STATUSES) . '.';
            }
        }

        if (array_key_exists('start_date', $data) && !self::isValidDate($data['start_date'])) {
            $errors['start_date'] = 'Data de início inválida. Use o formato YYYY-MM-DD.';
        }

        if (array_key_exists('end_date', $data) && !self::isValidDate($data['end_date'])) {
            $errors['end_date'] = 'Data de fim inválida. Use o formato YYYY-MM-DD.';
        }

        if (empty($errors['start_date']) && empty($errors['end_date'])
            && isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['end_date'] < $data['start_date']) {
                $errors['end_date'] = 'A data de fim deve ser maior ou igual à data de início.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados inválidos.', $errors);
        }
    }

    private static function isValidDate(mixed $date): bool
    {
        if (!is_string($date)) return false;
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
