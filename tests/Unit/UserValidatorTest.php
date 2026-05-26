<?php

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Validators\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    public function testValidPayloadPasses(): void
    {
        $this->expectNotToPerformAssertions();
        UserValidator::validateCreate(['name' => 'João', 'email' => 'joao@example.com']);
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate(['name' => 'João', 'email' => 'nao-e-email']);
    }

    public function testMissingEmailThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate(['name' => 'João']);
    }

    public function testEmptyEmailThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate(['name' => 'João', 'email' => '']);
    }

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate(['name' => '', 'email' => 'joao@example.com']);
    }

    public function testMissingNameThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate(['email' => 'joao@example.com']);
    }

    public function testNameTooLongThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        UserValidator::validateCreate([
            'name'  => str_repeat('a', 256),
            'email' => 'joao@example.com',
        ]);
    }

    public function testValidEmailFormats(): void
    {
        $this->expectNotToPerformAssertions();
        $validEmails = [
            'user@domain.com',
            'user.name+tag@domain.co.uk',
            'user123@sub.domain.org',
        ];
        foreach ($validEmails as $email) {
            UserValidator::validateCreate(['name' => 'Nome', 'email' => $email]);
        }
    }
}
