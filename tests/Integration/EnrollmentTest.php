<?php

namespace Tests\Integration;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Services\EnrollmentService;
use Tests\IntegrationTestCase;

class EnrollmentTest extends IntegrationTestCase
{
    private EnrollmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EnrollmentService();
    }

    public function testEnrollUserInAvailableClass(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);

        $enrollment = $this->service->enroll($user['id'], $class['id']);

        $this->assertIsArray($enrollment);
        $this->assertSame($user['id'], (int)$enrollment['user_id']);
        $this->assertSame($class['id'], (int)$enrollment['course_class_id']);
        $this->assertNotNull($enrollment['id']);
    }

    public function testEnrollNonExistentUser(): void
    {
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);

        $this->expectException(NotFoundException::class);
        $this->service->enroll(9999, $class['id']);
    }

    public function testEnrollInNonExistentClass(): void
    {
        $user = $this->createUser();

        $this->expectException(NotFoundException::class);
        $this->service->enroll($user['id'], 9999);
    }

    public function testEnrollInClosedClass(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], ['status' => 'encerrado']);

        $this->expectException(BusinessRuleException::class);
        $this->service->enroll($user['id'], $class['id']);
    }

    public function testEnrollBeforeClassStartDate(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], [
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date'   => date('Y-m-d', strtotime('+40 days')),
        ]);

        $this->expectException(BusinessRuleException::class);
        $this->service->enroll($user['id'], $class['id']);
    }

    public function testEnrollAfterClassEndDate(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], [
            'start_date' => date('Y-m-d', strtotime('-60 days')),
            'end_date'   => date('Y-m-d', strtotime('-1 days')),
        ]);

        $this->expectException(BusinessRuleException::class);
        $this->service->enroll($user['id'], $class['id']);
    }

    public function testEnrollWhenNoSlotsAvailable(): void
    {
        $user1  = $this->createUser();
        $user2  = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], ['slots' => 1]);

        $this->service->enroll($user1['id'], $class['id']);

        $this->expectException(BusinessRuleException::class);
        $this->service->enroll($user2['id'], $class['id']);
    }

    public function testPreventDuplicateEnrollmentSameCourse(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class1 = $this->createClass($course['id'], ['title' => 'Turma A']);
        $class2 = $this->createClass($course['id'], ['title' => 'Turma B']);

        $this->service->enroll($user['id'], $class1['id']);

        $this->expectException(BusinessRuleException::class);
        $this->service->enroll($user['id'], $class2['id']);
    }

    public function testAllowEnrollmentInDifferentCourses(): void
    {
        $user    = $this->createUser();
        $course1 = $this->createCourse(['title' => 'Curso 1', 'topic' => 'tecnologia']);
        $course2 = $this->createCourse(['title' => 'Curso 2', 'topic' => 'marketing']);
        $class1  = $this->createClass($course1['id']);
        $class2  = $this->createClass($course2['id']);

        $e1 = $this->service->enroll($user['id'], $class1['id']);
        $e2 = $this->service->enroll($user['id'], $class2['id']);

        $this->assertNotNull($e1['id']);
        $this->assertNotNull($e2['id']);
    }

    public function testAllowDifferentUsersInSameClass(): void
    {
        $user1  = $this->createUser();
        $user2  = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], ['slots' => 10]);

        $e1 = $this->service->enroll($user1['id'], $class['id']);
        $e2 = $this->service->enroll($user2['id'], $class['id']);

        $this->assertNotNull($e1['id']);
        $this->assertNotNull($e2['id']);
    }

    public function testListUserEnrollments(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse(['title' => 'Meu Curso', 'topic' => 'tecnologia']);
        $class  = $this->createClass($course['id']);
        $this->service->enroll($user['id'], $class['id']);

        $result = $this->service->listUserEnrollments($user['id']);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('enrollments', $result);
        $this->assertCount(1, $result['enrollments']);
        $this->assertSame('Meu Curso', $result['enrollments'][0]['course']['title']);
    }

    public function testListEnrollmentsForNonExistentUser(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->listUserEnrollments(9999);
    }

    public function testListEnrollmentsForUserWithNoEnrollments(): void
    {
        $user   = $this->createUser();
        $result = $this->service->listUserEnrollments($user['id']);

        $this->assertSame($user['id'], (int)$result['user']['id']);
        $this->assertEmpty($result['enrollments']);
    }

    public function testEnrollmentBusinessRuleClosedClassCode(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], ['status' => 'encerrado']);

        try {
            $this->service->enroll($user['id'], $class['id']);
            $this->fail('Esperava BusinessRuleException');
        } catch (BusinessRuleException $e) {
            $this->assertSame('ENROLLMENT_CLASS_CLOSED', $e->getRule());
        }
    }

    public function testEnrollmentBusinessRuleDuplicateCourseCode(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class1 = $this->createClass($course['id'], ['title' => 'Turma A']);
        $class2 = $this->createClass($course['id'], ['title' => 'Turma B']);

        $this->service->enroll($user['id'], $class1['id']);

        try {
            $this->service->enroll($user['id'], $class2['id']);
            $this->fail('Esperava BusinessRuleException');
        } catch (BusinessRuleException $e) {
            $this->assertSame('ENROLLMENT_DUPLICATE_COURSE', $e->getRule());
        }
    }

    public function testEnrollmentBusinessRuleNoSlotsCode(): void
    {
        $user1  = $this->createUser();
        $user2  = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id'], ['slots' => 1]);

        $this->service->enroll($user1['id'], $class['id']);

        try {
            $this->service->enroll($user2['id'], $class['id']);
            $this->fail('Esperava BusinessRuleException');
        } catch (BusinessRuleException $e) {
            $this->assertSame('ENROLLMENT_NO_SLOTS', $e->getRule());
        }
    }

    public function testCancelEnrollmentSuccess(): void
    {
        $user       = $this->createUser();
        $course     = $this->createCourse();
        $class      = $this->createClass($course['id']);
        $enrollment = $this->createEnrollment($user['id'], $class['id']);

        $this->service->cancel((int)$enrollment['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE id = :id');
        $stmt->execute(['id' => $enrollment['id']]);
        $this->assertFalse($stmt->fetch(), 'A matrícula deve ser removida do banco após cancelamento');
    }

    public function testCancelNonExistentEnrollmentThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->cancel(9999);
    }
}
