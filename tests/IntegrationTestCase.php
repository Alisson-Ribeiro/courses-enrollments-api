<?php

namespace Tests;

use App\Config\Database;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        Database::reset();
        $this->pdo = Database::getConnection();
        $this->truncateTables();
    }

    private function truncateTables(): void
    {
        $this->pdo->exec(
            'TRUNCATE TABLE enrollments, course_classes, courses, users RESTART IDENTITY CASCADE'
        );
    }

    protected function createCourse(array $overrides = []): array
    {
        $defaults = [
            'title'       => 'Curso de Teste',
            'description' => 'Descrição de teste',
            'topic'       => 'tecnologia',
            'image_url'   => 'https://example.com/img.jpg',
        ];
        $data = array_merge($defaults, $overrides);

        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (title, description, topic, image_url) VALUES (:title, :description, :topic, :image_url) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch();
    }

    protected function createClass(int $courseId, array $overrides = []): array
    {
        $defaults = [
            'title'       => 'Turma de Teste',
            'description' => 'Descrição da turma',
            'slots'       => 20,
            'status'      => 'disponivel',
            'start_date'  => date('Y-m-d'),
            'end_date'    => date('Y-m-d', strtotime('+30 days')),
        ];
        $data = array_merge($defaults, $overrides);

        $stmt = $this->pdo->prepare(
            'INSERT INTO course_classes (course_id, title, description, slots, status, start_date, end_date)
             VALUES (:course_id, :title, :description, :slots, :status, :start_date, :end_date) RETURNING *'
        );
        $stmt->execute(array_merge(['course_id' => $courseId], $data));
        return $stmt->fetch();
    }

    protected function createUser(array $overrides = []): array
    {
        static $counter = 0;
        $counter++;
        $defaults = [
            'name'  => "Usuário Teste {$counter}",
            'email' => "usuario{$counter}@teste.com",
        ];
        $data = array_merge($defaults, $overrides);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email) VALUES (:name, :email) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch();
    }

    protected function createEnrollment(int $userId, int $classId): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO enrollments (user_id, course_class_id) VALUES (:user_id, :course_class_id) RETURNING *'
        );
        $stmt->execute(['user_id' => $userId, 'course_class_id' => $classId]);
        return $stmt->fetch();
    }
}
