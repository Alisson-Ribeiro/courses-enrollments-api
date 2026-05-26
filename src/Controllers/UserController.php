<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\UserService;

class UserController extends BaseController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function store(array $params): void
    {
        $user = $this->service->create($this->getBody());
        Response::created($user);
    }

    public function destroy(array $params): void
    {
        $this->service->delete((int)$params['id']);
        Response::noContent();
    }
}
