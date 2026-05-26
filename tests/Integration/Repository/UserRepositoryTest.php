<?php

namespace Tests\Integration\Repository;

use App\Repositories\UserRepository;
use Tests\IntegrationTestCase;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function testCreateReturnsInsertedRow(): void
    {
        $user = $this->repository->create(['name' => 'João Silva', 'email' => 'joao@teste.com']);

        $this->assertIsArray($user);
        $this->assertNotNull($user['id']);
        $this->assertSame('João Silva', $user['name']);
        $this->assertSame('joao@teste.com', $user['email']);
    }

    public function testFindByIdReturnsCorrectUser(): void
    {
        $created = $this->createUser(['name' => 'Maria', 'email' => 'maria@teste.com']);

        $found = $this->repository->findById((int)$created['id']);

        $this->assertIsArray($found);
        $this->assertSame((int)$created['id'], (int)$found['id']);
        $this->assertSame('Maria', $found['name']);
        $this->assertSame('maria@teste.com', $found['email']);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findById(9999);

        $this->assertNull($result);
    }

    public function testFindByEmailReturnsCorrectUser(): void
    {
        $this->createUser(['email' => 'busca@teste.com']);

        $found = $this->repository->findByEmail('busca@teste.com');

        $this->assertIsArray($found);
        $this->assertSame('busca@teste.com', $found['email']);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByEmail('inexistente@teste.com');

        $this->assertNull($result);
    }

    public function testFindByEmailIsCaseSensitive(): void
    {
        $this->createUser(['email' => 'user@teste.com']);

        $result = $this->repository->findByEmail('USER@TESTE.COM');

        $this->assertNull($result);
    }

    public function testDeleteRemovesRowAndReturnsBool(): void
    {
        $user = $this->createUser();

        $deleted = $this->repository->delete((int)$user['id']);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->findById((int)$user['id']));
    }

    public function testDeleteReturnsFalseWhenNotFound(): void
    {
        $result = $this->repository->delete(9999);

        $this->assertFalse($result);
    }

    public function testDeleteCascadesToEnrollments(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);
        $this->createEnrollment((int)$user['id'], (int)$class['id']);

        $this->repository->delete((int)$user['id']);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user['id']]);
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }

    public function testCreateWithDuplicateEmailThrows(): void
    {
        $this->createUser(['email' => 'duplicado@teste.com']);

        $this->expectException(\PDOException::class);
        $this->repository->create(['name' => 'Outro', 'email' => 'duplicado@teste.com']);
    }
}
