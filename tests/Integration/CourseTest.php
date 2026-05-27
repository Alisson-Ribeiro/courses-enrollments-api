<?php

namespace Tests\Integration;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\CourseService;
use Tests\IntegrationTestCase;

class CourseTest extends IntegrationTestCase
{
    private CourseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseService();
    }

    public function testCreateCourseSuccess(): void
    {
        $course = $this->service->create([
            'title'       => 'Marketing Digital',
            'description' => 'Aprenda marketing digital',
            'topic'       => 'marketing',
            'image_url'   => 'https://example.com/img.jpg',
        ]);

        $this->assertIsArray($course);
        $this->assertSame('Marketing Digital', $course['title']);
        $this->assertSame('marketing', $course['topic']);
        $this->assertNotNull($course['id']);
    }

    public function testCreateCourseWithInvalidTopic(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            'title' => 'Curso Inválido',
            'topic' => 'culinaria',
        ]);
    }

    public function testCreateCourseWithEmptyTitle(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            'title' => '',
            'topic' => 'tecnologia',
        ]);
    }

    public function testCreateCourseWithMissingTopic(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create(['title' => 'Curso Sem Tema']);
    }

    public function testUpdateCourse(): void
    {
        $course = $this->createCourse();

        $updated = $this->service->update($course['id'], [
            'title' => 'Título Atualizado',
            'topic' => 'inovacao',
        ]);

        $this->assertSame('Título Atualizado', $updated['title']);
        $this->assertSame('inovacao', $updated['topic']);
    }

    public function testUpdateCourseNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->update(9999, ['title' => 'Novo Título']);
    }

    public function testUpdateCourseWithInvalidTopic(): void
    {
        $course = $this->createCourse();
        $this->expectException(ValidationException::class);
        $this->service->update($course['id'], ['topic' => 'invalido']);
    }

    public function testDeleteCourse(): void
    {
        $course = $this->createCourse();
        $this->service->delete($course['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute(['id' => $course['id']]);
        $this->assertFalse($stmt->fetch());
    }

    public function testDeleteCourseAlsoDeletesClasses(): void
    {
        $course = $this->createCourse();
        $class  = $this->createClass($course['id']);

        $this->service->delete($course['id']);

        $stmt = $this->pdo->prepare('SELECT * FROM course_classes WHERE id = :id');
        $stmt->execute(['id' => $class['id']]);
        $this->assertFalse($stmt->fetch(), 'A turma deve ser removida via CASCADE');
    }

    public function testDeleteCourseNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->delete(9999);
    }

    public function testListCoursesWithAvailableClasses(): void
    {
        $course = $this->createCourse(['title' => 'Curso Visível', 'topic' => 'tecnologia']);
        $this->createClass($course['id']);

        $results = $this->service->listWithAvailableClasses();

        $this->assertCount(1, $results['data']);
        $this->assertSame('Curso Visível', $results['data'][0]['title']);
        $this->assertNotEmpty($results['data'][0]['classes']);
    }

    public function testListCoursesDoesNotReturnClosedClasses(): void
    {
        $course = $this->createCourse();
        $this->createClass($course['id'], ['status' => 'encerrado']);

        $results = $this->service->listWithAvailableClasses();
        $this->assertCount(0, $results['data']);
    }

    public function testListCoursesDoesNotReturnClassesOutsideDateRange(): void
    {
        $course = $this->createCourse();
        $this->createClass($course['id'], [
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date'   => date('Y-m-d', strtotime('+40 days')),
        ]);

        $results = $this->service->listWithAvailableClasses();
        $this->assertCount(0, $results['data'], 'Turmas com início futuro não devem aparecer na listagem');
    }

    public function testListCoursesFilterByTitle(): void
    {
        $c1 = $this->createCourse(['title' => 'Marketing Digital']);
        $c2 = $this->createCourse(['title' => 'Tecnologia Avançada']);
        $this->createClass($c1['id']);
        $this->createClass($c2['id']);

        $results = $this->service->listWithAvailableClasses(['title' => 'Marketing']);

        $this->assertCount(1, $results['data']);
        $this->assertSame('Marketing Digital', $results['data'][0]['title']);
    }

    public function testListCoursesFilterByTopic(): void
    {
        $c1 = $this->createCourse(['title' => 'Curso Agro', 'topic' => 'agro']);
        $c2 = $this->createCourse(['title' => 'Curso Tech', 'topic' => 'tecnologia']);
        $this->createClass($c1['id']);
        $this->createClass($c2['id']);

        $results = $this->service->listWithAvailableClasses(['topic' => 'agro']);

        $this->assertCount(1, $results['data']);
        $this->assertSame('agro', $results['data'][0]['topic']);
    }

    public function testListCoursesFilterByTitleAndTopic(): void
    {
        $c1 = $this->createCourse(['title' => 'Marketing Digital', 'topic' => 'marketing']);
        $c2 = $this->createCourse(['title' => 'Marketing de Produto', 'topic' => 'empreendedorismo']);
        $this->createClass($c1['id']);
        $this->createClass($c2['id']);

        $results = $this->service->listWithAvailableClasses([
            'title' => 'Marketing',
            'topic' => 'marketing',
        ]);

        $this->assertCount(1, $results['data']);
        $this->assertSame('Marketing Digital', $results['data'][0]['title']);
    }

    public function testListCoursesReturnsPaginationStructure(): void
    {
        $course = $this->createCourse();
        $this->createClass($course['id']);

        $result = $this->service->listWithAvailableClasses();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['data']);
        $this->assertIsInt($result['total']);
    }

    public function testListCoursesPaginationLimitsResults(): void
    {
        foreach (['A', 'B', 'C'] as $i => $letter) {
            $topics = ['tecnologia', 'marketing', 'inovacao'];
            $c = $this->createCourse(['title' => "Curso {$letter}", 'topic' => $topics[$i]]);
            $this->createClass($c['id']);
        }

        $result = $this->service->listWithAvailableClasses([], 1, 2);

        $this->assertCount(2, $result['data']);
        $this->assertSame(3, $result['total']);
    }

    public function testListCoursesPage2ReturnsCorrectSlice(): void
    {
        foreach (['A', 'B', 'C'] as $i => $letter) {
            $topics = ['tecnologia', 'marketing', 'inovacao'];
            $c = $this->createCourse(['title' => "Curso {$letter}", 'topic' => $topics[$i]]);
            $this->createClass($c['id']);
        }

        $result = $this->service->listWithAvailableClasses([], 2, 2);

        $this->assertCount(1, $result['data']);
        $this->assertSame(3, $result['total']);
    }
}
