<?php

namespace App\Repositories;

use App\Config\Database;

class CourseRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (title, description, topic, image_url)
             VALUES (:title, :description, :topic, :image_url)
             RETURNING *'
        );
        $stmt->execute([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'topic'       => $data['topic'],
            'image_url'   => $data['image_url'] ?? null,
        ]);

        return $stmt->fetch();
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['title', 'description', 'topic', 'image_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE courses SET ' . implode(', ', $fields) . ' WHERE id = :id RETURNING *';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findAllWithAvailableClasses(array $filters = []): array
    {
        $params = [];
        $where  = ["cc.status = 'disponivel'", 'CURRENT_DATE BETWEEN cc.start_date AND cc.end_date'];

        if (!empty($filters['title'])) {
            $where[] = "c.title ILIKE :title";
            $params['title'] = '%' . $filters['title'] . '%';
        }

        if (!empty($filters['topic'])) {
            $where[] = "c.topic = :topic";
            $params['topic'] = $filters['topic'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT
                c.id,
                c.title,
                c.description,
                c.topic,
                c.image_url,
                c.created_at,
                c.updated_at,
                json_agg(
                    json_build_object(
                        'id',          cc.id,
                        'title',       cc.title,
                        'description', cc.description,
                        'slots',       cc.slots,
                        'status',      cc.status,
                        'start_date',  cc.start_date,
                        'end_date',    cc.end_date
                    ) ORDER BY cc.start_date
                ) AS classes
            FROM courses c
            INNER JOIN course_classes cc ON cc.course_id = c.id
            {$whereClause}
            GROUP BY c.id
            ORDER BY c.title
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(function (array $row) {
            $row['classes'] = json_decode($row['classes'], true);
            return $row;
        }, $rows);
    }
}
