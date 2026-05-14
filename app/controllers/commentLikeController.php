<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/commentLikes.php';

class commentLikeController extends Controller
{
    public function index()
    {
        $commentLikeModel = new commentLikes();
        $commmentLikes = $commentLikeModel->getAllCommentLikes();

        $this->json($commentLikes);
    }
}
