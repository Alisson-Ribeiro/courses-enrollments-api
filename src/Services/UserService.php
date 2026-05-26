<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\NotFoundException;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

class UserService
{
    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function create(array $data): array
    {
        UserValidator::validateCreate($data);

        if ($this->repository->findByEmail($data['email']) !== null) {
            throw new BusinessRuleException(
                "O email '{$data['email']}' já está cadastrado.",
                'EMAIL_ALREADY_EXISTS'
            );
        }

        return $this->repository->create($data);
    }

    public function delete(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new NotFoundException("Usuário {$id} não encontrado.");
        }

        $this->repository->delete($id);
    }

    public function findOrFail(int $id): array
    {
        $user = $this->repository->findById($id);
        if ($user === null) {
            throw new NotFoundException("Usuário {$id} não encontrado.");
        }
        return $user;
    }
}
