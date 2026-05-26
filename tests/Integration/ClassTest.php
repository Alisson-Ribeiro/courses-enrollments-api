<?php

namespace Tests\Integration;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\ClassService;
use Tests\IntegrationTestCase;

class ClassTest extends IntegrationTestCase
{
    private ClassService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClassService();
    }

    public function testCreateClassForExistingCourse(): void
    {
        $course = $this->createCourse();

        $class = $this->service->create($course['id'], [
            'title'      => 'Turma A',
            'slots'      => 25,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);

        $this->assertIsArray($class);
        $this->assertSame('Turma A', $class['title']);
        $this->assertSame(25, (int)$class['slots']);
        $this->assertSame($course['id'], (int)$class['course_id']);
    }

    public function testCreateClassForNonExistentCourse(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->create(9999, [
            'title'      => 'Turma',
            'slots'      => 10,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);
    }

    public function testCreateClassWithEndDateBeforeStart(): void
    {
        $course = $this->createCourse();
        $this->expectException(ValidationException::class);

        $this->service->create($course['id'], [
            'title'      => 'Turma Inválida',
            'slots'      => 10,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date'   => date('Y-m-d'),
        ]);
    }

    public function testCreateClassWithNegativeSlots(): void
    {
        $course = $this->createCourse();
        $this->expectException(ValidationException::class);

        $this->service->create($course['id'], [
            'title'      => 'Turma Inválida',
            'slots'      => -5,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);
    }

    public function testCreateClassWithZeroSlots(): void
    {
        $course = $this->createCourse();
        $this->expectException(ValidationException::class);

        $this->service->create($course['id'], [
            'title'      => 'Turma Inválida',
            'slots'      => 0,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);
    }

    public function testCreateClassWithInvalidStatus(): void
    {
        $course = $this->createCourse();
        $this->expectException(ValidationException::class);

        $this->service->create($course['id'], [
            'title'      => 'Turma Inválida',
            'slots'      => 10,
            'status'     => 'invalido',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);
    }

    public function testUpdateClass(): void
    {
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);

        $updated = $this->service->update($course['id'], $class['id'], [
            'title' => 'Turma Atualizada',
            'slots' => 50,
        ]);

        $this->assertSame('Turma Atualizada', $updated['title']);
        $this->assertSame(50, (int)$updated['slots']);
    }

    public function testUpdateClassNotFound(): void
    {
        $course = $this->createCourse();
        $this->expectException(NotFoundException::class);
        $this->service->update($course['id'], 9999, ['title' => 'X']);
    }

    public function testUpdateClassFromWrongCourse(): void
    {
        $course1 = $this->createCourse();
        $course2 = $this->createCourse(['title' => 'Outro Curso', 'topic' => 'agro']);
        $class   = $this->createClass($course1['id']);

        $this->expectException(NotFoundException::class);
        $this->service->update($course2['id'], $class['id'], ['title' => 'Novo Nome']);
    }

    public function testDeleteClass(): void
    {
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);

        $this->service->delete($course['id'], $class['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM course_classes WHERE id = :id');
        $stmt->execute(['id' => $class['id']]);
        $this->assertFalse($stmt->fetch());
    }

    public function testDeleteClassNotFound(): void
    {
        $course = $this->createCourse();
        $this->expectException(NotFoundException::class);
        $this->service->delete($course['id'], 9999);
    }

    public function testOneCourseCanHaveMultipleClasses(): void
    {
        $course = $this->createCourse();

        $this->service->create($course['id'], [
            'title'      => 'Turma A',
            'slots'      => 10,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+30 days')),
        ]);

        $this->service->create($course['id'], [
            'title'      => 'Turma B',
            'slots'      => 15,
            'status'     => 'disponivel',
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+60 days')),
        ]);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM course_classes WHERE course_id = :id');
        $stmt->execute(['id' => $course['id']]);
        $this->assertSame(2, (int)$stmt->fetchColumn());
    }
}
