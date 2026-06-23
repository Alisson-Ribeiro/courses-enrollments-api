<?php

namespace Tests\Integration\Repository;

use App\Repositories\ClassRepository;
use Tests\IntegrationTestCase;

class ClassRepositoryTest extends IntegrationTestCase
{
    private ClassRepository $repository;
    private array $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ClassRepository();
        $this->course     = $this->createCourse();
    }

    public function testCreateReturnsInsertedRow(): void
    {
        $class = $this->repository->create((int)$this->course['id'], [
            'title'       => 'Turma A',
            'description' => 'Descrição',
            'slots'       => 30,
            'status'      => 'disponivel',
            'start_date'  => date('Y-m-d'),
            'end_date'    => date('Y-m-d', strtotime('+30 days')),
        ]);

        $this->assertIsArray($class);
        $this->assertNotNull($class['id']);
        $this->assertSame((int)$this->course['id'], (int)$class['course_id']);
        $this->assertSame('Turma A', $class['title']);
        $this->assertSame(30, (int)$class['slots']);
    }

    public function testFindByIdReturnsCorrectClass(): void
    {
        $created = $this->createClass((int)$this->course['id'], ['title' => 'Turma B']);

        $found = $this->repository->findById((int)$created['id']);

        $this->assertIsArray($found);
        $this->assertSame((int)$created['id'], (int)$found['id']);
        $this->assertSame('Turma B', $found['title']);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findById(9999);

        $this->assertNull($result);
    }

    public function testUpdateChangesFieldsAndSetsUpdatedAt(): void
    {
        $class = $this->createClass((int)$this->course['id'], ['title' => 'Original', 'slots' => 10]);

        $updated = $this->repository->update((int)$class['id'], ['title' => 'Atualizado', 'slots' => 50]);

        $this->assertSame('Atualizado', $updated['title']);
        $this->assertSame(50, (int)$updated['slots']);
        $this->assertNotNull($updated['updated_at']);
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $class = $this->createClass((int)$this->course['id'], ['title' => 'Turma C', 'slots' => 20]);

        $updated = $this->repository->update((int)$class['id'], ['title' => 'Novo Título']);

        $this->assertSame('Novo Título', $updated['title']);
        $this->assertSame(20, (int)$updated['slots']);
    }

    public function testUpdateWithEmptyArrayReturnsCourseUnchanged(): void
    {
        $class = $this->createClass((int)$this->course['id'], ['title' => 'Sem Mudança']);
        $updatedAtBefore = $class['updated_at'];

        $result = $this->repository->update((int)$class['id'], []);

        $this->assertSame('Sem Mudança', $result['title']);
        $this->assertSame($updatedAtBefore, $result['updated_at']);
    }

    public function testUpdateReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->update(9999, ['title' => 'X']);

        $this->assertNull($result);
    }

    public function testDeleteRemovesRowAndReturnsBool(): void
    {
        $class = $this->createClass((int)$this->course['id']);

        $deleted = $this->repository->delete((int)$class['id']);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->findById((int)$class['id']));
    }

    public function testDeleteReturnsFalseWhenNotFound(): void
    {
        $result = $this->repository->delete(9999);

        $this->assertFalse($result);
    }

    public function testFindByCourseAndIdReturnsClass(): void
    {
        $class = $this->createClass((int)$this->course['id']);

        $found = $this->repository->findByCourseAndId((int)$this->course['id'], (int)$class['id']);

        $this->assertIsArray($found);
        $this->assertSame((int)$class['id'], (int)$found['id']);
    }

    public function testFindByCourseAndIdReturnsNullForWrongCourse(): void
    {
        $otherCourse = $this->createCourse(['title' => 'Outro Curso']);
        $class       = $this->createClass((int)$this->course['id']);

        $result = $this->repository->findByCourseAndId((int)$otherCourse['id'], (int)$class['id']);

        $this->assertNull($result);
    }

    public function testFindByCourseAndIdReturnsNullWhenClassNotFound(): void
    {
        $result = $this->repository->findByCourseAndId((int)$this->course['id'], 9999);

        $this->assertNull($result);
    }

    public function testLockByIdReturnsExistingClass(): void
    {
        $class = $this->createClass((int)$this->course['id'], ['title' => 'Turma Lock']);

        $result = $this->repository->lockById((int)$class['id']);

        $this->assertIsArray($result);
        $this->assertSame((int)$class['id'], (int)$result['id']);
        $this->assertSame('Turma Lock', $result['title']);
    }

    public function testLockByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->lockById(9999);

        $this->assertNull($result);
    }

    public function testUpdateWithSameValuesDoesNotChangeUpdatedAt(): void
    {
        $class = $this->createClass((int)$this->course['id'], ['title' => 'Título Fixo', 'slots' => 10]);
        $updatedAtBefore = $class['updated_at'];

        $result = $this->repository->update((int)$class['id'], ['title' => 'Título Fixo', 'slots' => 10]);

        $this->assertSame('Título Fixo', $result['title']);
        $this->assertSame($updatedAtBefore, $result['updated_at'], 'updated_at não deve mudar quando os valores são idênticos');
    }
}
