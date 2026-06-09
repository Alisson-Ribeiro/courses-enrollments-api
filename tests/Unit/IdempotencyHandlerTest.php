<?php

namespace Tests\Unit;

use App\Helpers\IdempotencyHandler;
use PHPUnit\Framework\TestCase;

class IdempotencyHandlerTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function uniqueKey(): string
    {
        $key  = 'test-' . uniqid('', true);
        $hash = 'ik_' . hash('sha256', $key);
        $this->tempFiles[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $hash . '.json';
        return $key;
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $result = IdempotencyHandler::get($this->uniqueKey());
        $this->assertNull($result);
    }

    public function testStoreAndGetRoundtrip(): void
    {
        $key = $this->uniqueKey();
        IdempotencyHandler::store($key, 201, '{"id":1}');

        $result = IdempotencyHandler::get($key);
        $this->assertNotNull($result);
        $this->assertSame(201, $result['status']);
        $this->assertSame('{"id":1}', $result['body']);
    }

    public function testExpiredEntryReturnsNull(): void
    {
        $key  = $this->uniqueKey();
        $hash = 'ik_' . hash('sha256', $key);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $hash . '.json';

        file_put_contents($path, json_encode([
            'status'     => 200,
            'body'       => 'ok',
            'expires_at' => time() - 1,
        ]));

        $result = IdempotencyHandler::get($key);
        $this->assertNull($result);
    }

    public function test4xxStatusIsCached(): void
    {
        $key = $this->uniqueKey();
        IdempotencyHandler::store($key, 422, '{"error":"validation"}');

        $result = IdempotencyHandler::get($key);
        $this->assertNotNull($result);
        $this->assertSame(422, $result['status']);
    }

    public function testIsValidKeyRejectsEmpty(): void
    {
        $this->assertFalse(IdempotencyHandler::isValidKey(''));
    }

    public function testIsValidKeyRejectsTooLong(): void
    {
        $this->assertFalse(IdempotencyHandler::isValidKey(str_repeat('a', 129)));
    }

    public function testIsValidKeyAcceptsUuid(): void
    {
        $this->assertTrue(IdempotencyHandler::isValidKey('550e8400-e29b-41d4-a716-446655440000'));
    }

    public function testIsValidKeyAcceptsMaxLength(): void
    {
        $this->assertTrue(IdempotencyHandler::isValidKey(str_repeat('a', 128)));
    }

    public function testCorruptFileReturnsNull(): void
    {
        $key  = $this->uniqueKey();
        $hash = 'ik_' . hash('sha256', $key);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $hash . '.json';

        file_put_contents($path, 'not-valid-json');

        $result = IdempotencyHandler::get($key);
        $this->assertNull($result);
    }
}
