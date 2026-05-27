<?php

namespace Tests\Integration\Repository;

use App\Repositories\EnrollmentRepository;
use Tests\IntegrationTestCase;

class EnrollmentRepositoryTest extends IntegrationTestCase
{
    private EnrollmentRepository $repository;
    private array $user;
    private array $course;
    private array $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EnrollmentRepository();
        $this->user       = $this->createUser();
        $this->course     = $this->createCourse();
        $this->class      = $this->createClass((int)$this->course['id']);
    }

    public function testCreateReturnsInsertedRow(): void
    {
        $enrollment = $this->repository->create((int)$this->user['id'], (int)$this->class['id']);

        $this->assertIsArray($enrollment);
        $this->assertNotNull($enrollment['id']);
        $this->assertSame((int)$this->user['id'], (int)$enrollment['user_id']);
        $this->assertSame((int)$this->class['id'], (int)$enrollment['course_class_id']);
        $this->assertNotNull($enrollment['enrolled_at']);
    }

    public function testCountByClassReturnsZeroWhenNoEnrollments(): void
    {
        $count = $this->repository->countByClass((int)$this->class['id']);

        $this->assertSame(0, $count);
    }

    public function testCountByClassReturnsCorrectCount(): void
    {
        $user2 = $this->createUser();
        $user3 = $this->createUser();
        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$user2['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$user3['id'], (int)$this->class['id']);

        $count = $this->repository->countByClass((int)$this->class['id']);

        $this->assertSame(3, $count);
    }

    public function testCountByClassCountsOnlyTargetClass(): void
    {
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $class2  = $this->createClass((int)$course2['id']);
        $user2   = $this->createUser();

        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$user2['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$this->user['id'], (int)$class2['id']);

        $this->assertSame(2, $this->repository->countByClass((int)$this->class['id']));
        $this->assertSame(1, $this->repository->countByClass((int)$class2['id']));
    }

    public function testFindByUserAndCourseReturnsEnrollment(): void
    {
        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);

        $result = $this->repository->findByUserAndCourse((int)$this->user['id'], (int)$this->course['id']);

        $this->assertIsArray($result);
        $this->assertSame((int)$this->user['id'], (int)$result['user_id']);
    }

    public function testFindByUserAndCourseReturnsNullWhenNotEnrolled(): void
    {
        $result = $this->repository->findByUserAndCourse((int)$this->user['id'], (int)$this->course['id']);

        $this->assertNull($result);
    }

    public function testFindByUserAndCourseReturnsNullForDifferentCourse(): void
    {
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);

        $result = $this->repository->findByUserAndCourse((int)$this->user['id'], (int)$course2['id']);

        $this->assertNull($result);
    }

    public function testFindByUserReturnsEmptyArrayWhenNoEnrollments(): void
    {
        $result = $this->repository->findByUser((int)$this->user['id']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByUserReturnsAllEnrollments(): void
    {
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $class2  = $this->createClass((int)$course2['id']);
        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$this->user['id'], (int)$class2['id']);

        $result = $this->repository->findByUser((int)$this->user['id']);

        $this->assertCount(2, $result);
    }

    public function testFindByUserReturnsCorrectAliasedColumns(): void
    {
        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);

        $result = $this->repository->findByUser((int)$this->user['id']);
        $row    = $result[0];

        $expectedKeys = [
            'enrollment_id', 'enrolled_at',
            'course_id', 'course_title', 'course_topic', 'course_image_url',
            'class_id', 'class_title', 'class_description', 'class_slots',
            'class_status', 'class_start_date', 'class_end_date',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $row, "Coluna com alias '{$key}' não encontrada no resultado");
        }
    }

    public function testFindByUserIsOrderedByEnrolledAtDesc(): void
    {
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $class2  = $this->createClass((int)$course2['id']);

        $stmt = $this->pdo->prepare(
            "INSERT INTO enrollments (user_id, course_class_id, enrolled_at)
             VALUES (:user_id, :course_class_id, :enrolled_at) RETURNING *"
        );
        $stmt->execute([
            'user_id'         => $this->user['id'],
            'course_class_id' => $this->class['id'],
            'enrolled_at'     => '2026-01-01 10:00:00',
        ]);
        $stmt->execute([
            'user_id'         => $this->user['id'],
            'course_class_id' => $class2['id'],
            'enrolled_at'     => '2026-06-01 10:00:00',
        ]);

        $result = $this->repository->findByUser((int)$this->user['id']);

        $this->assertSame((int)$class2['id'], (int)$result[0]['class_id']);
        $this->assertSame((int)$this->class['id'], (int)$result[1]['class_id']);
    }

    public function testFindByUserDoesNotReturnOtherUsersEnrollments(): void
    {
        $user2   = $this->createUser();
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $class2  = $this->createClass((int)$course2['id']);

        $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);
        $this->createEnrollment((int)$user2['id'], (int)$class2['id']);

        $result = $this->repository->findByUser((int)$this->user['id']);

        $this->assertCount(1, $result);
        $this->assertSame((int)$this->class['id'], (int)$result[0]['class_id']);
    }

    public function testFindByIdReturnsEnrollment(): void
    {
        $enrollment = $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);

        $result = $this->repository->findById((int)$enrollment['id']);

        $this->assertIsArray($result);
        $this->assertSame((int)$enrollment['id'], (int)$result['id']);
        $this->assertSame((int)$this->user['id'], (int)$result['user_id']);
        $this->assertSame((int)$this->class['id'], (int)$result['course_class_id']);
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->findById(9999);

        $this->assertNull($result);
    }

    public function testDeleteRemovesEnrollmentAndReturnsTrue(): void
    {
        $enrollment = $this->createEnrollment((int)$this->user['id'], (int)$this->class['id']);

        $deleted = $this->repository->delete((int)$enrollment['id']);

        $this->assertTrue($deleted);

        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $enrollment['id']]);
        $this->assertFalse($stmt->fetch(), 'A matrícula deve ser removida do banco');
    }

    public function testDeleteReturnsFalseForNonExistent(): void
    {
        $result = $this->repository->delete(9999);

        $this->assertFalse($result);
    }
}
