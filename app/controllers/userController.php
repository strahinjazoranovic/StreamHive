<?php

require_once __DIR__ . '/../models/user.php';

class userController
{
    public function index()
    {
        $userModel = new user();

        $users = $userModel->getAllUsers();

        header('Content-Type: application/json');

        echo json_encode($users);
    }
}