<?php

require_once __DIR__ . '/controller.php';

class AuthController extends Controller
{
    public function login()
    {
        $this->render('login/login', ['basePath' => $this->getBasePath()]);
    }

    public function register()
    {
        $this->render('login/register', ['basePath' => $this->getBasePath()]);
    }
}
