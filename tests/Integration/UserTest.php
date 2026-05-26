<?php

namespace Tests\Integration;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\UserService;
use Tests\IntegrationTestCase;

class UserTest extends IntegrationTestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    public function testCreateUserSuccess(): void
    {
        $user = $this->service->create([
            'name'  => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $this->assertIsArray($user);
        $this->assertSame('João Silva', $user['name']);
        $this->assertSame('joao@example.com', $user['email']);
        $this->assertNotNull($user['id']);
    }

    public function testCreateUserWithInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            'name'  => 'Usuário',
            'email' => 'email-invalido',
        ]);
    }

    public function testCreateUserWithEmptyName(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            'name'  => '',
            'email' => 'user@example.com',
        ]);
    }

    public function testCreateUserWithMissingEmail(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(['name' => 'Usuário']);
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $this->service->create(['name' => 'Usuário 1', 'email' => 'duplicado@example.com']);

        $this->expectException(BusinessRuleException::class);

        $this->service->create(['name' => 'Usuário 2', 'email' => 'duplicado@example.com']);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = $this->service->create(['name' => 'Para Deletar', 'email' => 'delete@example.com']);

        $this->service->delete($user['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        $this->assertFalse($stmt->fetch());
    }

    public function testDeleteUserNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->delete(9999);
    }

    public function testDeleteUserAlsoDeletesEnrollments(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);
        $this->createEnrollment($user['id'], $class['id']);

        $this->service->delete($user['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM enrollments WHERE user_id = :id');
        $stmt->execute(['id' => $user['id']]);
        $this->assertFalse($stmt->fetch(), 'Matrículas devem ser removidas via CASCADE');
    }
}
