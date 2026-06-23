<?php

namespace App\Repositories;

use App\Config\Database;

class ClassRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(int $courseId, array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_classes (course_id, title, description, slots, status, start_date, end_date)
             VALUES (:course_id, :title, :description, :slots, :status, :start_date, :end_date)
             RETURNING *'
        );
        $stmt->execute([
            'course_id'   => $courseId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'slots'       => (int)$data['slots'],
            'status'      => $data['status'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
        ]);

        return $stmt->fetch();
    }

    public function update(int $id, array $data): ?array
    {
        $current = $this->findById($id);
        if ($current === null) {
            return null;
        }

        $fields = [];
        $params = ['id' => $id];

        // Só inclui no UPDATE campos que realmente mudaram — evita sobrescrever updated_at
        // quando o payload não traz alteração efetiva.
        foreach (['title', 'description', 'slots', 'status', 'start_date', 'end_date'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $incoming = $field === 'slots' ? (int)$data[$field] : $data[$field];
            $existing = $field === 'slots' ? (int)$current[$field] : $current[$field];
            if ($incoming === $existing) {
                continue;
            }
            $fields[] = "{$field} = :{$field}";
            $params[$field] = $incoming;
        }

        if (empty($fields)) {
            return $current;
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE course_classes SET ' . implode(', ', $fields) . ' WHERE id = :id RETURNING *';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCourseAndId(int $courseId, int $classId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM course_classes WHERE id = :id AND course_id = :course_id'
        );
        $stmt->execute(['id' => $classId, 'course_id' => $courseId]);
        return $stmt->fetch() ?: null;
    }

    // Deve ser chamado dentro de uma transação ativa. Bloqueia a linha da turma para
    // serializar matrículas concorrentes, evitando overbooking mesmo com tabela vazia.
    public function lockById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_classes WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
