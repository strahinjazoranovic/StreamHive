<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';

class VideoController extends Controller
{
    public function index()
    {
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();

        $this->json($videos);
    }
}
