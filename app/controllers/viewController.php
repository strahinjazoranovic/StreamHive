<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';

class viewController extends Controller
{
    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    public function index()
    {
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/index', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function admin()
    {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=home');
            exit;
        }
        $this->render('home/admin', ['basePath' => $this->getBasePath()]);
    }

    public function subscriptions(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $videos = $videoModel->getAllSubscribedVideos();
        // $subscriptions = $subscriptionModel->getUserSubscriptions($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/subscription', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function library(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $likedVideos = $videoModel->getLikedVideosByUser($_SESSION['user_id']);
        // $watchLaterVideos = $videoModel->getWatchLaterVideosByUser($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/library', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }

    public function history(){
        $this->ensureSessionStarted();
        $videoModel = new Video();
        $videos = $videoModel->getAllVideos();
        // $historyVideos = $videoModel->getHistoryVideosByUser($_SESSION['user_id']);
        $isLoggedIn = isset($_SESSION['user_id']);

        $this->render('home/history', [
            'basePath' => $this->getBasePath(),
            'videos' => $videos,
            'isLoggedIn' => $isLoggedIn,
        ]);
    }
}
