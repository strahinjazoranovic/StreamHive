<?php

require_once __DIR__ . '/../models/user.php';

class UserController
{
    public function index()
    {
        $userModel = new User();

        $users = $userModel->getAllUsers();

        header('Content-Type: application/json');

        echo json_encode($users);
    }
}