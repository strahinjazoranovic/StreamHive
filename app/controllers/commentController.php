<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/comment.php';

class commentController extends Controller
{
    public function index()
    {
        $commentModel = new comment();
        $commments = $commentModel->getAllComments();

        $this->json($comments);
    }
}
