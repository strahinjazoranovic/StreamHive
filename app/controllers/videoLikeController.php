<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/videoLikes.php';

class videoLikeController extends Controller
{
    public function index()
    {
        $videoLikeModel = new VideoLikes();
        $videoLikes = $videoLikeModel->getAllVideoLikes();

        $this->json($videoLikes);
    }
}
