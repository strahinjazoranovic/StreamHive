<?php
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/comment.php';

class commentController extends Controller
{
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Redirect to the video the user is currently watching
    private function redirectToVideo(int $videoId, string $extraQuery = ''): void
    {
        $videoPath = $this->getBasePath() . '/index.php?route=video&id=' . $videoId;
        header('Location: ' . $videoPath . $extraQuery);
        exit;
    }

    // Manage comment actions
    public function manage(): void
    {
        $this->ensureSessionStarted();

        // Only allow POST requests to manage comments
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=incorrect_method&code=405');
            exit;
        }

        // If the user is not logged in redirect him to the login page
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=login');
            exit;
        }

        $videoId = (int) ($_POST['videoId'] ?? 0);

        // If the video id is not valid make the user return to an error page
        if ($videoId <= 0) {
            header('Location: ' . $this->getBasePath() . '/index.php?route=error&type=not_found&code=404');
            exit;
        }

        $userId = (int) $_SESSION['user_id'];
        $action = trim((string) ($_POST['action'] ?? 'create'));
        $commentModel = new Comment();

        if ($action === 'delete') {
            $commentId = (int) ($_POST['commentId'] ?? 0);
            if ($commentId > 0) {
                $commentModel->deleteCommentByIdAndUserId($commentId, $userId);
            }

            $this->redirectToVideo($videoId);
        }

        if ($action === 'edit') {
            $commentId = (int) ($_POST['commentId'] ?? 0);
            $commentText = trim((string) ($_POST['comment'] ?? ''));
            $cleanText = preg_replace('/\s*\(edited\)$/i', '', $commentText);
            $cleanText = trim((string) ($cleanText ?? ''));

            if ($commentId <= 0) {
                $this->redirectToVideo($videoId);
            }

            if ($cleanText === '') {
                $this->redirectToVideo($videoId, '&editComment=' . $commentId . '#comment-' . $commentId);
            }

            $commentModel->updateCommentByIdAndUserId($commentId, $userId, $cleanText . ' (edited)');
            $this->redirectToVideo($videoId, '#comment-' . $commentId);
        }

        $commentText = trim((string) ($_POST['comment'] ?? ''));
        if ($commentText !== '') {
            $commentModel->createComment($videoId, $userId, $commentText);
        }

        $this->redirectToVideo($videoId);
    }
}
