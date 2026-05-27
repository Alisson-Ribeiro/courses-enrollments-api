<?php

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Validators\CourseValidator;
use PHPUnit\Framework\TestCase;

class CourseValidatorTest extends TestCase
{
    public function testValidTopics(): void
    {
        foreach (CourseValidator::VALID_TOPICS as $topic) {
            $this->expectNotToPerformAssertions();
            CourseValidator::validateCreate(['title' => 'Curso', 'topic' => $topic]);
        }
    }

    public function testInvalidTopicThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate(['title' => 'Curso', 'topic' => 'financas']);
    }

    public function testEmptyTitleThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate(['title' => '', 'topic' => 'tecnologia']);
    }

    public function testMissingTitleThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate(['topic' => 'tecnologia']);
    }

    public function testMissingTopicThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate(['title' => 'Curso']);
    }

    public function testInvalidImageUrlThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate([
            'title'     => 'Curso',
            'topic'     => 'tecnologia',
            'image_url' => 'nao-e-uma-url',
        ]);
    }

    public function testEmptyImageUrlThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate([
            'title'     => 'Curso',
            'topic'     => 'tecnologia',
            'image_url' => '',
        ]);
    }

    public function testValidImageUrl(): void
    {
        $this->expectNotToPerformAssertions();
        CourseValidator::validateCreate([
            'title'     => 'Curso',
            'topic'     => 'tecnologia',
            'image_url' => 'https://example.com/imagem.jpg',
        ]);
    }

    public function testTitleTooLongThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateCreate([
            'title' => str_repeat('a', 256),
            'topic' => 'tecnologia',
        ]);
    }

    public function testUpdateWithInvalidTopicThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        CourseValidator::validateUpdate(['topic' => 'invalido']);
    }

    public function testUpdateWithValidTopicPasses(): void
    {
        $this->expectNotToPerformAssertions();
        CourseValidator::validateUpdate(['topic' => 'marketing']);
    }

    public function testUpdateWithEmptyDataPasses(): void
    {
        $this->expectNotToPerformAssertions();
        CourseValidator::validateUpdate([]);
    }
}
