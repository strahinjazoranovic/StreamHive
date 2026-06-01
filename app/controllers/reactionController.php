<?php
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/video.php';
require_once __DIR__ . '/../models/comment.php';
require_once __DIR__ . '/../models/like.php';

class reactionController extends Controller
{
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Manage reactions on video
    public function video(): void
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        // Videos
        $videoId = (int) ($_POST['videoId'] ?? 0);
        $reaction = trim((string) ($_POST['reaction'] ?? ''));

        if ($videoId <= 0 || !in_array($reaction, ['like', 'dislike'], true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid reaction payload.',
            ], 422);
            return;
        }

        $videoModel = new Video();
        $video = $videoModel->getVideoByIdForShare($videoId);

        if ($video === null) {
            $this->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
            return;
        }

        $reactionModel = new Like();
        $userId = (int) $_SESSION['user_id'];
        $nextReactionType = $reaction === 'like';
        $currentReaction = $reactionModel->setUserVideoReaction($videoId, $userId, $nextReactionType);
        $counts = $reactionModel->getVideoReactionCounts($videoId);
        // Return JSON with likes and dislikes for videos and comments
        $this->json([
            'success' => true,
            'likes' => (int) ($counts['likes'] ?? 0),
            'dislikes' => (int) ($counts['dislikes'] ?? 0),
            'currentReaction' => $currentReaction === null
                ? null
                : ($currentReaction ? 'like' : 'dislike'),
        ]);
    }

    // Manage reactions on comment
    public function comment(): void
    {
        $this->ensureSessionStarted();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Invalid request method.',
            ], 405);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            $this->json([
                'success' => false,
                'message' => 'Please log in first.',
                'redirect' => $this->getBasePath() . '/index.php?route=login',
            ], 401);
            return;
        }

        $commentId = (int) ($_POST['commentId'] ?? 0);
        $reaction = trim((string) ($_POST['reaction'] ?? ''));

        if ($commentId <= 0 || !in_array($reaction, ['like', 'dislike'], true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid reaction payload.',
            ], 422);
            return;
        }

        $commentModel = new Comment();
        $comment = $commentModel->getCommentByIdForShare($commentId);

        if ($comment === null) {
            $this->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
            return;
        }

        $reactionModel = new Like();
        $userId = (int) $_SESSION['user_id'];
        $nextReactionType = $reaction === 'like';
        $currentReaction = $reactionModel->setUserCommentReaction($commentId, $userId, $nextReactionType);
        $counts = $reactionModel->getCommentReactionCounts($commentId);

        $this->json([
            'success' => true,
            'likes' => (int) ($counts['likes'] ?? 0),
            'dislikes' => (int) ($counts['dislikes'] ?? 0),
            'currentReaction' => $currentReaction === null
                ? null
                : ($currentReaction ? 'like' : 'dislike'),
        ]);
    }
}
