<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';

class HomeController extends Controller
{
    public function index()
    {
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();

        $this->render('home/index', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
        ]);
    }

    public function admin()
    {
        $this->render('home/admin', ['basePath' => $this->getBasePath()]);
    }
}
