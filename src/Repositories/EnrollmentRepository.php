<?php

namespace App\Repositories;

use App\Config\Database;

class EnrollmentRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function create(int $userId, int $classId): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO enrollments (user_id, course_class_id)
             VALUES (:user_id, :course_class_id)
             RETURNING *'
        );
        $stmt->execute([
            'user_id'        => $userId,
            'course_class_id' => $classId,
        ]);

        return $stmt->fetch();
    }

    public function countByClass(int $classId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM enrollments WHERE course_class_id = :class_id'
        );
        $stmt->execute(['class_id' => $classId]);
        return (int)$stmt->fetchColumn();
    }

    public function findByUserAndCourse(int $userId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*
             FROM enrollments e
             INNER JOIN course_classes cc ON cc.id = e.course_class_id
             WHERE e.user_id = :user_id
               AND cc.course_id = :course_id'
        );
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId]);
        return $stmt->fetch() ?: null;
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                e.id            AS enrollment_id,
                e.enrolled_at,
                c.id            AS course_id,
                c.title         AS course_title,
                c.topic         AS course_topic,
                c.image_url     AS course_image_url,
                cc.id           AS class_id,
                cc.title        AS class_title,
                cc.description  AS class_description,
                cc.slots        AS class_slots,
                cc.status       AS class_status,
                cc.start_date   AS class_start_date,
                cc.end_date     AS class_end_date
             FROM enrollments e
             INNER JOIN course_classes cc ON cc.id = e.course_class_id
             INNER JOIN courses c ON c.id = cc.course_id
             WHERE e.user_id = :user_id
             ORDER BY e.enrolled_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
