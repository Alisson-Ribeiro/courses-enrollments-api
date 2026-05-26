<?php

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Validators\ClassValidator;
use PHPUnit\Framework\TestCase;

class ClassValidatorTest extends TestCase
{
    private function validPayload(): array
    {
        return [
            'title'      => 'Turma Teste',
            'slots'      => 20,
            'status'     => 'disponivel',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-09-30',
        ];
    }

    public function testValidPayloadPasses(): void
    {
        $this->expectNotToPerformAssertions();
        ClassValidator::validateCreate($this->validPayload());
    }

    public function testEndDateBeforeStartDateThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate(array_merge($this->validPayload(), [
            'start_date' => '2026-09-30',
            'end_date'   => '2026-07-01',
        ]));
    }

    public function testSameDatesAreAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        ClassValidator::validateCreate(array_merge($this->validPayload(), [
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-01',
        ]));
    }

    public function testNegativeSlotsThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate(array_merge($this->validPayload(), ['slots' => -1]));
    }

    public function testZeroSlotsThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate(array_merge($this->validPayload(), ['slots' => 0]));
    }

    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate(array_merge($this->validPayload(), ['status' => 'pendente']));
    }

    public function testValidStatuses(): void
    {
        foreach (ClassValidator::VALID_STATUSES as $status) {
            $this->expectNotToPerformAssertions();
            ClassValidator::validateCreate(array_merge($this->validPayload(), ['status' => $status]));
        }
    }

    public function testInvalidDateFormatThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate(array_merge($this->validPayload(), ['start_date' => '01/07/2026']));
    }

    public function testMissingTitleThrowsException(): void
    {
        $payload = $this->validPayload();
        unset($payload['title']);
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate($payload);
    }

    public function testMissingStartDateThrowsException(): void
    {
        $payload = $this->validPayload();
        unset($payload['start_date']);
        $this->expectException(ValidationException::class);
        ClassValidator::validateCreate($payload);
    }

    public function testUpdateWithEmptyDataPasses(): void
    {
        $this->expectNotToPerformAssertions();
        ClassValidator::validateUpdate([]);
    }

    public function testUpdateWithInvalidStatusThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        ClassValidator::validateUpdate(['status' => 'ativo']);
    }
}
