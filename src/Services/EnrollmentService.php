<?php

namespace App\Services;

use App\Config\Database;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Repositories\ClassRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\UserRepository;

class EnrollmentService
{
    private UserRepository       $userRepository;
    private ClassRepository      $classRepository;
    private EnrollmentRepository $enrollmentRepository;

    public function __construct()
    {
        $this->userRepository       = new UserRepository();
        $this->classRepository      = new ClassRepository();
        $this->enrollmentRepository = new EnrollmentRepository();
    }

    public function enroll(int $userId, int $classId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new NotFoundException("Usuário {$userId} não encontrado.");
        }

        $class = $this->classRepository->findById($classId);
        if ($class === null) {
            throw new NotFoundException("Turma {$classId} não encontrada.");
        }

        if ($class['status'] === 'encerrado') {
            throw new BusinessRuleException(
                'Não é possível se matricular em uma turma encerrada.',
                'ENROLLMENT_CLASS_CLOSED'
            );
        }

        $today = new \DateTimeImmutable('today');
        $start = new \DateTimeImmutable($class['start_date']);
        $end   = new \DateTimeImmutable($class['end_date']);

        if ($today < $start) {
            throw new BusinessRuleException(
                'As matrículas para esta turma ainda não estão abertas.',
                'ENROLLMENT_NOT_STARTED'
            );
        }

        if ($today > $end) {
            throw new BusinessRuleException(
                'O período de matrícula para esta turma foi encerrado.',
                'ENROLLMENT_EXPIRED'
            );
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $occupied = $this->enrollmentRepository->countByClass($classId);
            if ($occupied >= (int)$class['slots']) {
                $pdo->rollBack();
                throw new BusinessRuleException(
                    'Não há vagas disponíveis nesta turma.',
                    'ENROLLMENT_NO_SLOTS'
                );
            }

            $existing = $this->enrollmentRepository->findByUserAndCourse($userId, (int)$class['course_id']);
            if ($existing !== null) {
                $pdo->rollBack();
                throw new BusinessRuleException(
                    'Você já está matriculado em uma turma deste curso.',
                    'ENROLLMENT_DUPLICATE_COURSE'
                );
            }

            $enrollment = $this->enrollmentRepository->create($userId, $classId);
            $pdo->commit();

            return $enrollment;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listUserEnrollments(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new NotFoundException("Usuário {$userId} não encontrado.");
        }

        $rows = $this->enrollmentRepository->findByUser($userId);

        $enrollments = array_map(fn(array $row) => [
            'enrollment_id' => $row['enrollment_id'],
            'enrolled_at'   => $row['enrolled_at'],
            'course'        => [
                'id'        => $row['course_id'],
                'title'     => $row['course_title'],
                'topic'     => $row['course_topic'],
                'image_url' => $row['course_image_url'],
            ],
            'class'         => [
                'id'          => $row['class_id'],
                'title'       => $row['class_title'],
                'description' => $row['class_description'],
                'slots'       => $row['class_slots'],
                'status'      => $row['class_status'],
                'start_date'  => $row['class_start_date'],
                'end_date'    => $row['class_end_date'],
            ],
        ], $rows);

        return [
            'user'        => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
            ],
            'enrollments' => $enrollments,
        ];
    }
}
